<?php

if (! defined('ABSPATH')) {
    exit;
}


$args = [
  'post_type' => ppcart_query_post_types('order'),
  'post_status' => ['paid','completed'],
  'posts_per_page' => $limit,
];

$meta_query = ['relation' => 'OR'];

if (is_user_logged_in()) {
    $meta_query[] = [
                        'key' => ppcart_meta_key('user_account'),
                        'value' => get_current_user_id(),
                    ];
}

$posted_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
if ($posted_email) {
    $meta_query[] = [
                'key' => ppcart_meta_key('email'),
        'value' => sanitize_email($posted_email),
            ];
}

$meta_query = apply_filters('ppcart_current_user_orders_meta_query_args', $meta_query, $product_id);

if (count($meta_query) > 1) {
    if ($product_id) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Combining user and product filters for order lookup.
        $args['meta_query'] = [
                'relation' => 'AND',
                [
                  'key' => ppcart_meta_key('product_id'),
                  'value' => $product_id,
                ],
                $meta_query,
        ];
    } else {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Querying current user orders by stored meta keys.
        $args['meta_query'] = $meta_query;
    }

    $order_posts = get_posts($args);
    return $order_posts;
} else {
    return false;
}
