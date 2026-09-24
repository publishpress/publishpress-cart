<?php

if (! defined('ABSPATH')) {
    exit;
}


header('Content-Type: application/json; charset=' . get_option('blog_charset'));

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce presence is checked before delegating verification to ppcart_check_ajax_referer().
if (!isset($_POST['nonce']) || !ppcart_check_ajax_referer('ppcart_ajax_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => __('Invalid nonce.', 'publishpress-cart')]);
}

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Order ID is consumed only after ppcart_check_ajax_referer() succeeds above.
$order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;
if (!$order_id || ! ppcart_is_order_post_type(get_post_type($order_id))) {
    wp_send_json_error(['message' => __('Invalid order ID.', 'publishpress-cart')]);
}

if (!current_user_can('edit_post', $order_id)) {
    wp_send_json_error(['message' => __('Unauthorized access.', 'publishpress-cart')]);
}

$cart_order = new PPCart_Order($order_id);
if (!$cart_order->id) {
    wp_send_json_error(['message' => __('Error loading order.', 'publishpress-cart')]);
}

try {
    $result = $this->sync_stripe_order($cart_order);
    wp_send_json_success(['message' => $result ?: __('Order synced successfully.', 'publishpress-cart')]);
} catch (Exception $e) {
    wp_send_json_error(['message' => $e->getMessage()]);
}
