<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_debug_logger;

if (! $this->is_connect_destination_configured()) {
    $ppcart_debug_logger->log_debug('Stripe Connect destination is missing or invalid during payment intent update.', 4);
    wp_send_json_error(['error' => $this->get_connect_configuration_error_message()]);
}

$nonce = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$nonce = is_string($nonce) ? sanitize_text_field($nonce) : '';

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    wp_send_json_error(['error' => __('Invalid Request', 'publishpress-cart')]);
}

$apikey = $ppcart_stripe['sk'];

$stripe = ppcart_stripe_client($apikey);

$echo = false;

$ppcart_product_id = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$ppcart_product_id = (false !== $ppcart_product_id && null !== $ppcart_product_id) ? absint($ppcart_product_id) : 0;
if ($ppcart_product_id) {
    ppcart_setup_product($ppcart_product_id);
}

$ppcart_order = new PPCart_Order();
$ppcart_order->load_from_post();
$ppcart_order = apply_filters('ppcart_after_order_load_from_post', $ppcart_order);

$amount = $ppcart_order->amount;
$amount_for_stripe = ppcart_price_in_cents($amount, $ppcart_currency);

// this is an ajax call, get pid from post data
if (!$pid) {
    $intent_id = filter_input(INPUT_POST, 'intent_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pid = is_string($intent_id) ? sanitize_text_field($intent_id) : '';
    $echo = true;
}

$ppcart_debug_logger->log_event(
    'checkout.payment_intent.updating',
    'Updating Stripe PaymentIntent amount before checkout confirmation.',
    [
        'payment_intent_id'   => $pid,
        'amount'              => $amount,
        'amount_stripe_units' => $amount_for_stripe,
        'currency'            => $ppcart_currency,
    ]
);

// Update payment intent amount
try {
    $update_args = [
        'amount' => $amount_for_stripe,
    ];
    $update_args = $this->add_connect_args_to_payment_intent($update_args, $amount_for_stripe);

    if (! ppcart_stripe_connect_fee_is_valid($update_args, $amount_for_stripe)) {
        $ppcart_debug_logger->log_event(
            'checkout.payment_intent.invalid_connect_fee',
            'Stripe PaymentIntent Connect fee was missing or invalid during amount update.',
            [
                'payment_intent_id' => $pid,
            ],
            4
        );
        wp_send_json_error([ 'error' => __('This checkout could not be started. Please contact the site administrator.', 'publishpress-cart') ]);
    }

    $intent = $stripe->paymentIntents->update(
        $pid,
        $update_args
    );
} catch (Exception $e) {
    $ppcart_debug_logger->log_event(
        'checkout.payment_intent.failed',
        'Stripe PaymentIntent amount update failed: ' . $e->getMessage(),
        [
            'payment_intent_id' => $pid,
        ],
        4
    );
    echo esc_html($e->getMessage());
    exit();
}

$ppcart_debug_logger->log_event(
    'checkout.payment_intent.updated',
    'Stripe PaymentIntent amount updated successfully.',
    [
        'payment_intent_id'   => $pid,
        'amount'              => $amount,
        'amount_stripe_units' => $amount_for_stripe,
        'currency'            => $ppcart_currency,
    ],
    0
);

// Persist the recalculated amount when updating an existing order.
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Order ID is consumed only after ppcart_verify_nonce() succeeds above.
if (isset($_POST['ppcart_order_id'])) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Order ID is consumed only after ppcart_verify_nonce() succeeds above.
    $order_id = absint(wp_unslash($_POST['ppcart_order_id']));
    ppcart_update_post_meta($order_id, 'amount', $amount);
}

// Return early for ajax callers after updating the amount.
if ($echo) {
    echo esc_html($amount);
    exit();
}

return $amount;
