<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_debug_logger;
$nonce         = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$product_id_in = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$nonce         = is_string($nonce) ? sanitize_text_field($nonce) : '';
$product_id_in = (false !== $product_id_in && null !== $product_id_in) ? absint($product_id_in) : 0;

$ppcart_debug_logger->log_event(
    'checkout.purchase_limit.checking',
    'Checking customer purchase limit for this product.',
    [
        'product_id' => $product_id_in,
        'has_limit'  => isset($ppcart_product->customer_purchase_limit),
    ]
);

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    wp_send_json_error(
        [
            'error' => __('Invalid Request', 'publishpress-cart'),
        ]
    );
}

if (! $product_id_in) {
    wp_send_json_error(
        [
            'error' => __('There was a problem with your submission, please refresh the page and try again.', 'publishpress-cart'),
        ]
    );
}

if (!$ppcart_product || (int) $ppcart_product->ID !== $product_id_in) {
    $product_id = $product_id_in;
    $ppcart_product = ppcart_setup_product($product_id);
} else {
    $product_id = $ppcart_product->ID;
}

if (!isset($ppcart_product->customer_purchase_limit)) {
    return;
}

$limit = $ppcart_product->customer_limit ?? 1;
$order_posts = PPCart_Order::get_current_user_orders($limit, $product_id);
if (!empty($order_posts) && count($order_posts) >= $limit) {
    $ppcart_debug_logger->log_debug('Error: current user ID: ' . get_current_user_id() . ' has already purchased product ID: ' . $product_id, 4);

    $message = $ppcart_product->customer_limit_message ?? __('Sorry, you have already purchased this product!', 'publishpress-cart');
    wp_send_json_error([ 'error' => $message ]);
}
