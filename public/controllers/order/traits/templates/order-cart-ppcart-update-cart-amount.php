<?php

if (! defined('ABSPATH')) {
    exit;
}


$nonce = isset($_POST['ppcart-nonce']) && is_string($_POST['ppcart-nonce']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the nonce field for immediate verification.
    ? sanitize_text_field(wp_unslash($_POST['ppcart-nonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The value is verified immediately below.
    : '';

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
}

$ppcart_product_id = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$ppcart_product_id = (false !== $ppcart_product_id && null !== $ppcart_product_id) ? absint($ppcart_product_id) : 0;

// setup product info
$ppcart_product = ppcart_setup_product($ppcart_product_id);

// setup order info
$ppcart_order = new PPCart_Order();
$ppcart_order->load_from_post();
$ppcart_order = apply_filters('ppcart_after_order_load_from_post', $ppcart_order);

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Labels are consumed only after ppcart_verify_nonce() succeeds above.
$amount_due_label = isset($_POST['ppcart_amount_due_label']) && is_scalar($_POST['ppcart_amount_due_label'])
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Label is consumed only after ppcart_verify_nonce() succeeds above.
    ? sanitize_text_field(wp_unslash($_POST['ppcart_amount_due_label']))
    : esc_html__('Amount Due', 'publishpress-cart');
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Labels are consumed only after ppcart_verify_nonce() succeeds above.
$due_today_label  = isset($_POST['ppcart_due_today_label']) && is_scalar($_POST['ppcart_due_today_label'])
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Label is consumed only after ppcart_verify_nonce() succeeds above.
    ? sanitize_text_field(wp_unslash($_POST['ppcart_due_today_label']))
    : esc_html__('Due Today', 'publishpress-cart');

$response = ppcart_prepare_update_cart_amount_response($ppcart_order, $amount_due_label, $due_today_label);

wp_send_json($response);
