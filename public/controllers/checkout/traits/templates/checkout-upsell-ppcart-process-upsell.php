<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_debug_logger;
$nonce_post = isset($_POST['ppcart-nonce']) && is_string($_POST['ppcart-nonce']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the nonce field for immediate verification.
    ? sanitize_text_field(wp_unslash($_POST['ppcart-nonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The value is verified immediately below.
    : '';
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The order ID is required to build the nonce action verified below.
$order_id_post = isset($_POST['ppcart-order']) ? absint(sanitize_text_field(wp_unslash($_POST['ppcart-order']))) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The offer type is required to build the nonce action verified below.
$is_downsell = isset($_POST['downsell']);

if (! $order_id_post || '' === $nonce_post) {
    wp_send_json_error([ 'message' => __('Invalid Request', 'publishpress-cart') ], 400);
}

$oto_type = $is_downsell ? 'downsell' : 'upsell';
if (! ppcart_verify_nonce($nonce_post, 'ppcart_' . $oto_type . '-' . $order_id_post)) {
    wp_send_json_error([ 'message' => __('Invalid Request', 'publishpress-cart') ], 401);
}

$ppcart_debug_logger->log_debug("Processing upsell purchase");

if ($is_downsell) {
    $cart_order = PPCart_Order::child_of($order_id_post, 'downsell');
} else {
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

    // Charge only the payment method that paid the parent order, proven from its own
    // Stripe payment. Never the customer's default or another saved card.
    $paymethod_id = PPCart_Stripe_Checkout_Customer::get_order_payment_method_id($stripe, $order_id_post, $cart_order->customer_id);

    if (! $paymethod_id) {
        $ppcart_debug_logger->log_debug("Stripe payment method not found, aborting", 4);
        wp_send_json_error([ 'message' => 'No payment method found.' ]);
    }

    $ppcart_debug_logger->log_debug("Stripe payment method retrieved", 0);

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
        ppcart_store_stripe_owned_record($sub);
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

        // The upsell subscription bills the parent order's payment method, not the customer's default.
        $ppcart_upsell_pm_filter = function ($args) use ($paymethod_id) {
            if (is_array($args)) {
                $args['default_payment_method'] = $paymethod_id;
            }

            return $args;
        };
        add_filter('ppcart_checkout_stripe_subscription_args', $ppcart_upsell_pm_filter, PHP_INT_MAX);
        $subscription = PPCart_Public_Subscription_Checkout_Controller::instance()->create_stripe_subscription($cart_order, $sub);
        remove_filter('ppcart_checkout_stripe_subscription_args', $ppcart_upsell_pm_filter, PHP_INT_MAX);

        if (!$subscription) {
            wp_send_json_error([ 'message' => 'Something went wrong, please try again later.' ]);
        } else {
            $sub->sub_status = $subscription->status;
            $sub->status = $subscription->status;
            $sub->subscription_id = $subscription->id;
            $sub->sub_next_bill_date = ppcart_get_stripe_subscription_period_end($subscription);

            if ($sub->status == 'trialing' && !$sub->free_trial_days) {
                $sub->status = 'active';
                $sub->sub_status = 'active';
            }

            ppcart_store_stripe_owned_record($sub);
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
            $transaction_id = ppcart_get_stripe_invoice_transaction_id($latest_invoice);

            if ($transaction_id) {
                $cart_order->transaction_id = $transaction_id;
            }

            if (isset($subscription->status) && $subscription->status != 'incomplete') {
                $cart_order->status = 'paid';
                $cart_order->payment_status = 'paid';
            }
            ppcart_store_stripe_owned_record($cart_order);
            wp_send_json((int) $cart_order->id);
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

        // One charge per parent order and offer: a retry replays the first PaymentIntent instead of charging again.
        $idempotency_key = 'ppcart-' . $oto_type . '-' . $order_id_post . '-' . (isset($cart_order->us_offer) ? (int) $cart_order->us_offer : 1);

        try {
            $intent = $stripe->paymentIntents->create($args, [ 'idempotency_key' => $idempotency_key ]);
        } catch (Exception $e) {
            $err = $e->getTrace();
            $err = json_decode($err[0]['args'][2])->error;
            $error_code = $err->code;

            if ($error_code == 'authentication_required') {
                wp_send_json([
                'error' => 'authentication_required',
                'paymentMethod' => $paymethod_id,
                'clientSecret' => $err->payment_intent->client_secret,
                'intentId' => $err->payment_intent->id,
                                    ]);
            } else {
                wp_send_json_error([
                    'message' => 'There was a problem processing this payment, contact us for more assistance',
                                        ]);
            }
        }

        $ppcart_debug_logger->log_debug("Stripe order created", 0);

        if ($intent->status == 'succeeded') {
            $cart_order->status = 'paid';
        }

        $cart_order->payment_status = $intent->status;
        $cart_order->transaction_id = $intent->id;
        ppcart_store_stripe_owned_record($cart_order);
        PPCart_Stripe_Checkout_Customer::remember_order_payment_method($cart_order->id, $paymethod_id);
        wp_send_json((int) $cart_order->id);
    }
} else {
    ppcart_store_stripe_owned_record($cart_order);
    wp_send_json((int) $cart_order->id);
}
