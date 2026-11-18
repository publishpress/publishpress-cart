<?php

if (! defined('ABSPATH')) {
    exit;
}


$nonce = isset($_POST['ppcart-nonce']) && is_string($_POST['ppcart-nonce']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the nonce field for immediate verification.
    ? sanitize_text_field(wp_unslash($_POST['ppcart-nonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The value is verified immediately below.
    : '';

if (empty($nonce) || ! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    echo wp_json_encode([
        'error' => __("An error occurred, please refresh the page and try again.", "publishpress-cart"),
    ]);
    exit();
}

$cancel_url = filter_input(INPUT_POST, 'cancel_url', FILTER_SANITIZE_URL);

list($ppcart_order, $sub, $ppcart_product_id) = $this->paypal_build_checkout_order();
$this->paypal_store_checkout_order($ppcart_order, $sub, $ppcart_product_id, $cancel_url);
$sub = null === $sub ? '' : $sub;

$paypalUrl = $this->build_paypal_url($ppcart_order, $sub);
$paypalUrl = apply_filters('ppcart_paypal_checkout_url', $paypalUrl, $ppcart_order, $sub);
$res = ['url' => $paypalUrl];

echo wp_json_encode($res);
exit();
