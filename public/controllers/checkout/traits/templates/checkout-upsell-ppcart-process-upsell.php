<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_debug_logger;
$order_id_post = filter_input(INPUT_POST, 'ppcart-order', FILTER_VALIDATE_INT);
$nonce_post    = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$is_downsell   = null !== filter_input(INPUT_POST, 'downsell', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$order_id_post = (false !== $order_id_post && null !== $order_id_post) ? absint($order_id_post) : 0;
$nonce_post    = is_string($nonce_post) ? sanitize_text_field($nonce_post) : '';

$ppcart_debug_logger->log_debug("Processing upsell purchase");

if (! $order_id_post || '' === $nonce_post) {
    wp_send_json_error([ 'message' => __('Invalid Request', 'publishpress-cart') ], 400);
}

if ($is_downsell) {
    if (! ppcart_verify_nonce($nonce_post, 'ppcart_downsell-' . $order_id_post)) {
        wp_send_json_error([ 'message' => __('Invalid Request', 'publishpress-cart') ], 401);
    }
    $cart_order = PPCart_Order::child_of($order_id_post, 'downsell');
} else {
    if (! ppcart_verify_nonce($nonce_post, 'ppcart_upsell-' . $order_id_post)) {
        wp_send_json_error([ 'message' => __('Invalid Request', 'publishpress-cart') ], 401);
    }
    $cart_order = PPCart_Order::child_of($order_id_post, 'upsell');
}

// child_of() returns false when no matching offer exists; bail instead of fataling on a bool below.
if (! $cart_order || ! is_object($cart_order)) {
    $ppcart_debug_logger->log_debug('Upsell child order could not be built for parent #' . $order_id_post, 4);
    wp_send_json_error([ 'message' => __('This offer is no longer available.', 'publishpress-cart') ]);
}

if ($cart_order->pay_method == 'stripe') {
    if (! $this->is_connect_destination_configured()) {
        $ppcart_debug_logger->log_debug('Stripe Connect destination is missing or invalid during upsell charge.', 4);
        wp_send_json_error([ 'message' => $this->get_connect_configuration_error_message() ]);
    }

    $apikey = $ppcart_stripe['sk'];

    $ppcart_debug_logger->log_debug("Retrieving Stripe payment method");

    $stripe = ppcart_stripe_client($apikey);

    // Prefer the customer's default method, then a saved card, then Link — a pure Link payment saves a type=link method a card-only lookup misses.
    $paymethod_id = null;

    $customer = $stripe->customers->retrieve($cart_order->customer_id);
    if ($customer && ! empty($customer->invoice_settings->default_payment_method)) {
        $paymethod_id = $customer->invoice_settings->default_payment_method;
    }

    if (! $paymethod_id) {
        foreach ([ 'card', 'link' ] as $pm_type) {
            $payment_methods = $stripe->paymentMethods->all([
              'customer' => $cart_order->customer_id,
              'type' => $pm_type,
            ]);
            if (! empty($payment_methods->data)) {
                $paymethod_id = $payment_methods->data[0]->id;
                break;
            }
        }
    }

    if (! $paymethod_id) {
        $ppcart_debug_logger->log_debug("Stripe payment method not found, aborting", 4);
        wp_send_json_error([ 'message' => 'No payment method found.' ]);
    }

    $ppcart_debug_logger->log_debug("Stripe payment method retrieved", 0);

    if ($customer && !$customer->invoice_settings->default_payment_method) {
        $customer = $stripe->customers->update($cart_order->customer_id, ['invoice_settings' => ['default_payment_method' => $paymethod_id]]);
    }

    if ($cart_order->plan->type == 'recurring') {
        $ppcart_debug_logger->log_event(
            'checkout.upsell.subscription.saving',
            'Saving upsell subscription before creating the Stripe subscription.',
            [
                'order_id'   => (int) $cart_order->id,
                'product_id' => (int) $cart_order->product_id,
            ]
        );

        $sub = PPCart_Subscription::from_order($cart_order);
        $this->store_stripe_owned_record($sub);
        $cart_order->subscription_id = $sub->id;

        $ppcart_debug_logger->log_event(
            'checkout.upsell.subscription.saved',
            "Upsell subscription #{$sub->id} saved successfully with status {$sub->status}.",
            [
                'subscription_id' => (int) $sub->id,
                'order_id'        => (int) $cart_order->id,
                'product_id'      => (int) $sub->product_id,
                'status'          => $sub->status,
            ],
            0
        );

        $subscription = PPCart_Public_Subscription_Checkout_Controller::instance()->create_stripe_subscription($cart_order, $sub);

        if (!$subscription) {
            wp_send_json_error([ 'message' => 'Something went wrong, please try again later.' ]);
        } else {
            $sub->sub_status = $subscription->status;
            $sub->status = $subscription->status;
            $sub->subscription_id = $subscription->id;
            $sub->sub_next_bill_date = $this->get_stripe_resource_value($subscription, 'current_period_end', 0);

            if ($sub->status == 'trialing' && !$sub->free_trial_days) {
                $sub->status = 'active';
                $sub->sub_status = 'active';
            }

            $this->store_stripe_owned_record($sub);
            $ppcart_debug_logger->log_event(
                'checkout.upsell.subscription.synced',
                "Upsell subscription #{$sub->id} synced from Stripe with status {$sub->status}.",
                [
                    'subscription_id'        => (int) $sub->id,
                    'order_id'               => (int) $cart_order->id,
                    'stripe_subscription_id' => $subscription->id,
                    'status'                 => $sub->status,
                    'stripe_status'          => $subscription->status,
                ],
                0
            );

            $latest_invoice = $this->get_stripe_resource_value($subscription, 'latest_invoice', null);
            $transaction_id = $this->get_stripe_invoice_transaction_id($latest_invoice);

            if ($transaction_id) {
                $cart_order->transaction_id = $transaction_id;
            }

            if (isset($subscription->status) && $subscription->status != 'incomplete') {
                $cart_order->status = 'paid';
                $cart_order->payment_status = 'paid';
            }
            $this->store_stripe_owned_record($cart_order);
            echo esc_html($cart_order->id);
            exit();
        }
    } else {
        $descriptor = get_option('_ppcart_stripe_descriptor', false);
        if (!$descriptor) {
            $descriptor = get_bloginfo('name') ?? ppcart_get_public_product_name($cart_order->product_id);
        }

        $args = [
            'amount'   => ppcart_price_in_cents($cart_order->amount, $cart_order->currency),
            'currency' => $cart_order->currency,
            'customer' => $cart_order->customer_id,
            'payment_method' => $paymethod_id,
            'confirm' => true,
            'off_session' => true,
            'description' => ppcart_get_public_product_name($cart_order->product_id),
            'statement_descriptor_suffix' => preg_replace("/[^0-9a-zA-Z ]/", '', substr($descriptor, 0, 22)),
            'metadata' => [
                'origin' => get_site_url(),
                'ppcart_product_id' => $cart_order->product_id,
                'ppcart_' . $cart_order->order_type => true,
            ],
        ];

        $args = $this->add_connect_args_to_payment_intent($args, ppcart_price_in_cents($cart_order->amount, $cart_order->currency));

        if (! ppcart_stripe_connect_fee_is_valid($args, ppcart_price_in_cents($cart_order->amount, $cart_order->currency))) {
            $ppcart_debug_logger->log_debug('Stripe upsell PaymentIntent Connect fee was missing or invalid.', 4);
            wp_send_json_error([ 'message' => __('This checkout could not be started. Please contact the site administrator.', 'publishpress-cart') ]);
        }

        $ppcart_debug_logger->log_debug('Sending order to Stripe, params: ' . wp_json_encode($args));

        try {
            $intent = $stripe->paymentIntents->create($args);

            $ppcart_debug_logger->log_debug("Stripe order created", 0);

            if ($intent->status == 'succeeded') {
                $cart_order->status = 'paid';
            }

            $cart_order->payment_status = $intent->status;
            $cart_order->transaction_id = $intent->id;
            $this->store_stripe_owned_record($cart_order);
            echo esc_html($cart_order->id);
            exit();
        } catch (Exception $e) {
            $err = $e->getTrace();
            $err = json_decode($err[0]['args'][2])->error;
            $error_code = $err->code;

            if ($error_code == 'authentication_required') {
                wp_send_json([
                'error' => 'authentication_required',
                'paymentMethod' => $payment_methods->data[0]->id,
                'clientSecret' => $err->payment_intent->client_secret,
                'intentId' => $err->payment_intent->id,
                                    ]);
            } else {
                wp_send_json_error([
                    'message' => 'There was a problem processing this payment, contact us for more assistance',
                                        ]);
            }
        }
    }
} else {
    $this->store_stripe_owned_record($cart_order);
    echo esc_html($cart_order->id);
}

exit();
