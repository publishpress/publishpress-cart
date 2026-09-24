<?php

if (! defined('ABSPATH')) {
    exit;
}


$args = [
    'post_type'  => ppcart_query_post_types('subscription'),
    'post_status' => 'any',
    'posts_per_page' => 1,
  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Lookup by subscription meta identifiers.
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => ppcart_meta_key('stripe_subscription_id'),
            'value' => $sub_id,
        ],
        [
            'key' => ppcart_meta_key('subscription_id'),
            'value' => $sub_id,
        ],
    ],
];
$args = apply_filters('ppcart_get_sub_args', $args, $sub_id);
$subscriptions = get_posts($args);
if (empty($subscriptions)) {
    return false;
} else {
    $subscription_post_id = $subscriptions[0]->ID;
    return new self($subscription_post_id);
}
