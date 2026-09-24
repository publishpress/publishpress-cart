<?php

if (! defined('ABSPATH')) {
    exit;
}


$post_data = filter_input_array(
    INPUT_POST,
    [
        'nonce' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_id' => FILTER_VALIDATE_INT,
        'payment_method' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    ]
);
$nonce = isset($post_data['nonce']) && is_string($post_data['nonce']) ? sanitize_text_field($post_data['nonce']) : '';
$subscription_post_id = isset($post_data['post_id']) && false !== $post_data['post_id'] && null !== $post_data['post_id'] ? absint($post_data['post_id']) : 0;
$payment_method = isset($post_data['payment_method']) && is_string($post_data['payment_method']) ? sanitize_text_field($post_data['payment_method']) : '';

if (! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
    wp_send_json_error(['message' => __("Invalid Request", "publishpress-cart")], 401);
}

if (! is_user_logged_in()) {
    wp_send_json_error([ 'message' => __('Authentication required.', 'publishpress-cart') ], 401);
}

if (! $subscription_post_id) {
    wp_send_json_error([ 'message' => __('Invalid subscription.', 'publishpress-cart') ], 400);
}

$response = [];
$ppcart_stripe = PPCart_Stripe::instance();
$sub = new PPCart_Subscription($subscription_post_id);

if (! $sub->id) {
    wp_send_json_error([ 'message' => __('Invalid subscription.', 'publishpress-cart') ], 404);
}

$current_user_id = get_current_user_id();
$sub_user_id     = absint($sub->user_account);
$is_admin        = current_user_can('manage_options');

if (! $is_admin && $sub_user_id !== $current_user_id) {
    wp_send_json_error([ 'message' => __('Permission denied.', 'publishpress-cart') ], 403);
}

$ppcart_subscription_id = $sub->subscription_id;
$ppcart_customer_id = $sub->customer_id;

if (! $ppcart_subscription_id || '' === $payment_method) {
    wp_send_json_error([ 'message' => __('Invalid payment method request.', 'publishpress-cart') ], 400);
}

$stripe = $ppcart_stripe->stripe();

if (! $stripe) {
    wp_send_json_error([ 'message' => __('Payment processor is unavailable. Please try again later.', 'publishpress-cart') ], 503);
}

if ($sub->status == 'incomplete' || $sub->status == 'past_due' || $sub->status == 'pending-payment') {
    $invoice = $stripe->subscriptions->retrieve($ppcart_subscription_id, ['expand' => ['latest_invoice']]);
    $invoice = $invoice['latest_invoice'];

    if ($invoice->status == 'open' || $invoice->status == 'uncollectible') {
        $stripe->invoices->pay($invoice->id);
    }
}

$response = $ppcart_stripe->updatePaymentMethod(
    $ppcart_subscription_id,
    $payment_method,
    $ppcart_customer_id
);

if (false === $response) {
    wp_send_json_error([ 'message' => __('Unable to save the new card. Please check your payment details and try again.', 'publishpress-cart') ], 400);
}

if (is_object($response) && ! empty($response->id)) {
    $response = ['status' => 'success','message' => 'New card has been saved.'];
}

wp_send_json($response);
