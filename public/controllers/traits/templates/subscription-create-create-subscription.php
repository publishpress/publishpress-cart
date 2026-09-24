<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_debug_logger;
$post_data = ppcart_filter_input_array(
    INPUT_POST,
    [
        'ppcart-nonce'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'email'           => FILTER_SANITIZE_EMAIL,
        'customerId'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'paymentMethodId' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'first_name'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'last_name'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'phone'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'invoiceId'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'ppcart_product_id'   => FILTER_VALIDATE_INT,
    ]
);
$nonce = isset($post_data['ppcart-nonce']) && is_string($post_data['ppcart-nonce']) ? sanitize_text_field($post_data['ppcart-nonce']) : '';
$email = isset($post_data['email']) && is_string($post_data['email']) ? sanitize_email($post_data['email']) : '';
$customer_id = isset($post_data['customerId']) && is_string($post_data['customerId']) ? sanitize_text_field($post_data['customerId']) : '';
$paymethod_id = isset($post_data['paymentMethodId']) && is_string($post_data['paymentMethodId']) ? sanitize_text_field($post_data['paymentMethodId']) : '';
$first_name = isset($post_data['first_name']) && is_string($post_data['first_name']) ? sanitize_text_field($post_data['first_name']) : '';
$last_name = isset($post_data['last_name']) && is_string($post_data['last_name']) ? sanitize_text_field($post_data['last_name']) : '';
$invoice_id = isset($post_data['invoiceId']) && is_string($post_data['invoiceId']) ? sanitize_text_field($post_data['invoiceId']) : '';
$ppcart_product_id = isset($post_data['ppcart_product_id']) && false !== $post_data['ppcart_product_id'] && null !== $post_data['ppcart_product_id'] ? absint($post_data['ppcart_product_id']) : 0;

$ppcart_debug_logger->log_event(
    'checkout.subscription.submitted',
    'Subscription checkout form submitted.',
    [
        'product_id' => $ppcart_product_id,
    ]
);

// base order
if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    $ppcart_debug_logger->log_event(
        'checkout.security.failed',
        'Checkout security check failed before subscription checkout.',
        [
            'product_id' => $ppcart_product_id,
            'check'      => 'ppcart_purchase_nonce:3',
        ],
        4
    );

    wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
}

if (! $this->is_connect_destination_configured()) {
    $ppcart_debug_logger->log_debug('Stripe Connect destination is missing or invalid during subscription checkout.', 4);
    wp_send_json_error([ 'error' => $this->get_connect_configuration_error_message() ]);
}

$ppcart_debug_logger->log_event(
    'checkout.subscription.validation.started',
    'Subscription checkout validation hooks started.',
    [
        'product_id' => $ppcart_product_id,
    ]
);

// create base order
do_action('ppcart_before_create_main_order');

$ppcart_debug_logger->log_event(
    'checkout.subscription.validation.passed',
    'Subscription checkout validation hooks passed.',
    [
        'product_id' => $ppcart_product_id,
    ],
    0
);

$ppcart_order = new PPCart_Order();
$ppcart_order->load_from_post();
$ppcart_order = apply_filters('ppcart_after_order_load_from_post', $ppcart_order);
$ppcart_order->gateway_mode = $ppcart_stripe['mode'];

$apikey = $ppcart_stripe['sk'];
$stripe = ppcart_stripe_client($apikey);

$ppcart_debug_logger->log_event(
    'checkout.subscription.payment_method.retrieving',
    'Retrieving Stripe PaymentMethod for subscription checkout.',
    [
        'product_id'        => $ppcart_product_id,
        'payment_method_id' => $paymethod_id,
    ]
);

try {
    $payment_method = $stripe->paymentMethods->retrieve(
        $paymethod_id
    );
    $payment_method->attach([
        'customer' => $customer_id,
    ]);
} catch (Exception $e) {
    $ppcart_debug_logger->log_event(
        'checkout.subscription.payment_method.failed',
        'Stripe PaymentMethod retrieval failed: ' . $e->getMessage(),
        [
            'product_id' => $ppcart_product_id,
        ],
        4
    );

    wp_send_json_error([ 'error' => sanitize_text_field($e->getMessage()) ]);
}

$ppcart_debug_logger->log_event(
    'checkout.subscription.payment_method.retrieved',
    'Stripe PaymentMethod attached to the customer successfully.',
    [
        'product_id' => $ppcart_product_id,
    ],
    0
);

$args = [
        'name' => $first_name . ' ' . $last_name,
        'email' => $email,
        'invoice_settings' => [
            'default_payment_method' => $paymethod_id,
    ],
];

if (isset($post_data['phone']) && is_string($post_data['phone'])) {
    $args['phone'] = sanitize_text_field($post_data['phone']);
}

// Set the default payment method on the customer
$stripe->customers->update(
    $customer_id,
    $args
);

// retry Invoice With New Payment Method
if ('' !== $invoice_id) {
    $ppcart_debug_logger->log_event(
        'checkout.subscription.invoice.retrying',
        'Retrying Stripe invoice with the updated payment method.',
        [
            'invoice_id' => $invoice_id,
        ]
    );

    try {
        $invoice = $stripe->invoices->retrieve($invoice_id, [
            'expand' => ['confirmation_secret'],
        ]);
        wp_send_json($invoice);
    } catch (Exception $e) {
        $ppcart_debug_logger->log_event(
            'checkout.subscription.invoice.failed',
            'Stripe invoice retry failed: ' . $e->getMessage(),
            [
                'invoice_id' => $invoice_id,
            ],
            4
        );
    }
}

$ppcart_debug_logger->log_event(
    'checkout.subscription.saving',
    'Saving local subscription before creating the Stripe subscription.',
    [
        'product_id' => $ppcart_product_id,
    ]
);

// save subscription
$sub = PPCart_Subscription::from_order($ppcart_order);
$this->store_stripe_owned_record($sub);

$ppcart_order->subscription_id = $sub->id;
$this->store_stripe_owned_record($ppcart_order);

if ($sub->id) {
    $ppcart_debug_logger->log_event(
        'checkout.subscription.saved',
        "Subscription #{$sub->id} saved successfully with status {$sub->status}.",
        [
            'subscription_id' => (int) $sub->id,
            'order_id'        => (int) $ppcart_order->id,
            'product_id'      => (int) $sub->product_id,
            'status'          => $sub->status,
        ],
        0
    );
} else {
    $ppcart_debug_logger->log_event(
        'checkout.subscription.failed',
        'Subscription could not be saved before Stripe subscription creation.',
        [
            'order_id'   => (int) $ppcart_order->id,
            'product_id' => (int) $ppcart_order->product_id,
        ],
        4
    );
}

$subscription = $this->create_stripe_subscription($ppcart_order, $sub);

if (!$subscription) {
    $subscription = ['error' => 'Something went wrong, please try again later.'];
} else {
    $sub->sub_status = $subscription->status;
    $sub->status = $subscription->status;
    $sub->subscription_id = $subscription->id;
    $sub->sub_next_bill_date = $this->get_stripe_resource_value($subscription, 'current_period_end', 0);
    $sub->customer_id = $subscription->customer;
    $sub->cancel_at = $subscription->cancel_at;
    $sub->sub_end_date = gmdate('Y-m-d', $subscription->cancel_at);
    $ppcart_order->customer_id = $subscription->customer;

    if ($sub->status == 'trialing' && !$sub->free_trial_days) {
        $sub->status = 'active';
        $sub->sub_status = 'active';
    }

    $this->store_stripe_owned_record($sub);

    if ($sub->id) {
        $ppcart_debug_logger->log_event(
            'checkout.subscription.synced',
            "Subscription #{$sub->id} synced from Stripe with status {$sub->status}.",
            [
                'subscription_id'        => (int) $sub->id,
                'stripe_subscription_id' => $subscription->id,
                'status'                 => $sub->status,
                'stripe_status'          => $subscription->status,
            ],
            0
        );
    } else {
        $ppcart_debug_logger->log_event(
            'checkout.subscription.failed',
            'Subscription status could not be updated from Stripe.',
            [
                'stripe_subscription_id' => $subscription->id,
                'stripe_status'          => $subscription->status,
            ],
            4
        );
    }

    $latest_invoice = $this->get_stripe_resource_value($subscription, 'latest_invoice', null);
    $transaction_id = $this->get_stripe_invoice_transaction_id($latest_invoice);

    if ($transaction_id) {
        $ppcart_order->transaction_id = $transaction_id;
    }

    // if setting for webhook updates only, then evaluate to false
    $update_invoice = !get_option('_ppcart_stripe_invoice_webhook_updates_only', false);
    if (apply_filters('ppcart_update_stripe_invoice_during_checkout', $update_invoice, $ppcart_order, $sub)) {
        // run integrations
        if (isset($subscription->status) && $subscription->status != 'incomplete') {
            $ppcart_order->status = 'paid';
            $ppcart_order->payment_status = 'paid';
        }
        $this->store_stripe_owned_record($ppcart_order);

        if ($ppcart_order->id) {
            $ppcart_debug_logger->log_event(
                'checkout.order.synced',
                "Order #{$ppcart_order->id} updated to status {$ppcart_order->status} after Stripe subscription creation.",
                [
                    'order_id'              => (int) $ppcart_order->id,
                    'subscription_id'       => (int) $sub->id,
                    'stripe_subscription_id' => $subscription->id,
                    'status'                => $ppcart_order->status,
                ],
                0
            );
        } else {
            $ppcart_debug_logger->log_event(
                'checkout.order.failed',
                'Order status could not be updated after Stripe subscription creation.',
                [
                    'subscription_id'        => (int) $sub->id,
                    'stripe_subscription_id' => $subscription->id,
                ],
                4
            );
        }
    }

    // setup redirect
    $subscription->ppcart_order_id = $ppcart_order->id;
    $subscription->ppcart_access = PPCart_Order::ensure_invoice_access_token($ppcart_order->id);
    $ppcart_product = ppcart_setup_product($ppcart_product_id);
    if (!$ppcart_product->upsell_path && $ppcart_product->confirmation != 'redirect') {
        $subscription->formAction = PPCart_Order::confirmation_url($ppcart_product->thanks_url, $ppcart_order->id);
        $subscription->formAction = apply_filters('ppcart_host_purchase_url', $subscription->formAction, $ppcart_order->id, $ppcart_product_id);
    }

    if (!$subscription->formAction) {
        unset($subscription->formAction);
    }
}

wp_send_json($subscription);
