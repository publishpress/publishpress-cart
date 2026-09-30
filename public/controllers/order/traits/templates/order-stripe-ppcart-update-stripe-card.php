<?php

if (! defined('ABSPATH')) {
    exit;
}


$nonce = isset($_POST['nonce']) && is_string($_POST['nonce']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the nonce field for immediate verification.
    ? sanitize_text_field(wp_unslash($_POST['nonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The value is verified immediately below.
    : '';

if (! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
    wp_send_json_error(['message' => __("Invalid Request", "publishpress-cart")], 401);
}

$post_data = filter_input_array(
    INPUT_POST,
    [
        'post_id' => FILTER_VALIDATE_INT,
        'payment_method' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'all_subscription' => FILTER_VALIDATE_BOOLEAN,
    ]
);
$subscription_post_id = isset($post_data['post_id']) && false !== $post_data['post_id'] && null !== $post_data['post_id'] ? absint($post_data['post_id']) : 0;
$payment_method = isset($post_data['payment_method']) && is_string($post_data['payment_method']) ? sanitize_text_field($post_data['payment_method']) : '';
$all_subscriptions = ! empty($post_data['all_subscription']);

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

$response = $ppcart_stripe->updatePaymentMethod(
    $ppcart_subscription_id,
    $payment_method,
    $ppcart_customer_id
);

if (false === $response) {
    wp_send_json_error([ 'message' => __('Unable to save the new card. Please check your payment details and try again.', 'publishpress-cart') ], 400);
}

if ($all_subscriptions && $ppcart_stripe->setCustomerSubscriptionsPaymentMethod($ppcart_customer_id, $payment_method, $ppcart_subscription_id) > 0) {
    wp_send_json_error([ 'message' => __('The new card was saved, but some of your other subscriptions could not be updated. Please try again.', 'publishpress-cart') ], 500);
}

// Retry the open invoice only after the new card is saved, so it is charged to the new card.
if ($sub->status == 'incomplete' || $sub->status == 'past_due' || $sub->status == 'pending-payment') {
    try {
        $invoice = $stripe->subscriptions->retrieve($ppcart_subscription_id, ['expand' => ['latest_invoice']]);
        $invoice = $invoice['latest_invoice'];

        if ($invoice && ($invoice->status == 'open' || $invoice->status == 'uncollectible')) {
            $stripe->invoices->pay($invoice->id, ['payment_method' => $payment_method]);
        }
    } catch (\Exception $e) {
        ppcart_helper()->logException($e, __LINE__, __FILE__);
        wp_send_json_error([ 'message' => __('The new card was saved, but the payment failed: ', 'publishpress-cart') . $e->getMessage() ], 402);
    }
}

if (is_object($response) && ! empty($response->id)) {
    $response = ['status' => 'success','message' => 'New card has been saved.'];
}

wp_send_json($response);
