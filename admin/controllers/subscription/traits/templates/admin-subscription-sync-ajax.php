<?php

if (! defined('ABSPATH')) {
    exit;
}


// Set headers to ensure JSON response
header('Content-Type: application/json; charset=' . get_option('blog_charset'));

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce presence is checked before delegating verification to ppcart_check_ajax_referer().
if (!isset($_POST['nonce']) || !ppcart_check_ajax_referer('ppcart_ajax_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => __('Invalid nonce.', 'publishpress-cart')]);
}

if (! current_user_can('manage_options') && ! ppcart_user_can('manager_option')) {
    wp_send_json_error(['message' => __('Unauthorized access.', 'publishpress-cart')]);
}

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Subscription ID is consumed only after ppcart_check_ajax_referer() succeeds above.
$stripe_subscription_id = isset($_POST['stripe_subscription_id']) ? sanitize_text_field(wp_unslash($_POST['stripe_subscription_id'])) : '';
if (!$stripe_subscription_id) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Invalid IDs are useful to trace broken admin requests.
    error_log("Invalid Stripe subscription ID");
    wp_send_json_error(['message' => __('Invalid Stripe subscription ID.', 'publishpress-cart')]);
}

// Find ppcart_subscription post by Stripe subscription ID
$args = [
    'post_type' => ppcart_query_post_types('subscription'),
    'post_status' => 'any',
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Subscription lookup is intentionally keyed by Stripe subscription meta.
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => ppcart_meta_key('subscription_id'),
            'value' => $stripe_subscription_id,
            'compare' => '=',
        ],
        [
            'key' => ppcart_meta_key('stripe_subscription_id'),
            'value' => $stripe_subscription_id,
            'compare' => '=',
        ],
    ],
    'posts_per_page' => 1,
];
$subscriptions = get_posts($args);

if (empty($subscriptions)) {
    wp_send_json_error(['message' => __('Subscription not found in PublishPress Cart.', 'publishpress-cart')]);
}

$subscription_post = $subscriptions[0];

if (! current_user_can('edit_post', $subscription_post->ID)) {
    wp_send_json_error(['message' => __('Unauthorized access.', 'publishpress-cart')]);
}

$subscription = new PPCart_Subscription($subscription_post->ID);
if (!$subscription->id) {
    wp_send_json_error(['message' => __('Error loading subscription.', 'publishpress-cart')]);
}

try {
    $result = $this->sync_stripe_subscription($subscription);
    wp_send_json_success(['message' => $result ?: __('Subscription synced successfully.', 'publishpress-cart')]);
} catch (Exception $e) {
    wp_send_json_error(['message' => $e->getMessage()]);
}
