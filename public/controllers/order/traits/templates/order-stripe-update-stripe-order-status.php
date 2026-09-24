<?php

if (! defined('ABSPATH')) {
    exit;
}


$post_data = ppcart_filter_input_array(
    INPUT_POST,
    [
        'ppcart-nonce'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'paymentIntent' => [
            'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'flags'  => FILTER_REQUIRE_ARRAY,
        ],
        'ppcart_temp_order_id'    => FILTER_VALIDATE_INT,
        'ppcart_temp_order_token' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    ]
);
$nonce          = isset($post_data['ppcart-nonce']) && is_string($post_data['ppcart-nonce']) ? sanitize_text_field($post_data['ppcart-nonce']) : '';
$payment_intent = isset($post_data['paymentIntent']) ? ppcart_parse_stripe_payment_intent($post_data['paymentIntent'], false) : [];
$temp_order_id  = isset($post_data['ppcart_temp_order_id']) && false !== $post_data['ppcart_temp_order_id'] && null !== $post_data['ppcart_temp_order_id'] ? absint($post_data['ppcart_temp_order_id']) : 0;
$temp_token     = isset($post_data['ppcart_temp_order_token']) && is_string($post_data['ppcart_temp_order_token']) ? sanitize_text_field($post_data['ppcart_temp_order_token']) : '';

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
}

$response = $this->sanitize_order_status_response();

$response_order_id = isset($response['order_id']) ? absint($response['order_id']) : 0;
if ($response_order_id) {
    $stored_temp_token = ppcart_get_post_meta($response_order_id, 'temp_order_token', true);
    $response_has_temp_order = is_string($stored_temp_token) && '' !== $stored_temp_token;
    $has_valid_response_token = $response_has_temp_order && $temp_order_id && $temp_order_id === $response_order_id && '' !== $temp_token && hash_equals($stored_temp_token, $temp_token);

    if ($response_has_temp_order && ! $has_valid_response_token) {
        wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
    }
}

if ($temp_order_id && $temp_order_id !== $response_order_id) {
    wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
}

if ($this->is_finalized_temp_order_response($temp_order_id, $response_order_id)) {
    $this->clear_temp_order_meta($temp_order_id);
    $order_id = isset($response['order_id']) ? absint($response['order_id']) : 0;
    if ($order_id) {
        $response['ppcart_access'] = PPCart_Order::ensure_invoice_access_token($order_id);
    }
    wp_send_json($response);
}

if ($temp_order_id) {
    $stored_temp_token = ppcart_get_post_meta($temp_order_id, 'temp_order_token', true);

    if (! is_string($stored_temp_token) || '' === $stored_temp_token || '' === $temp_token || ! hash_equals($stored_temp_token, $temp_token)) {
        wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
    }
}

// Do integrations if charge status = suceeded
if (isset($payment_intent['status']) && 'succeeded' === $payment_intent['status'] && isset($response['order_id'])) {
    $order_id = absint($response['order_id']);
    $cart_order = new PPCart_Order($order_id);
    $cart_order->status = 'paid';
    $cart_order->payment_status = $payment_intent['status'];
    $this->store_stripe_owned_record($cart_order);
    $this->clear_temp_order_meta($order_id);
}

$order_id = isset($response['order_id']) ? absint($response['order_id']) : 0;
if ($order_id) {
    $response['ppcart_access'] = PPCart_Order::ensure_invoice_access_token($order_id);
}

wp_send_json($response);
