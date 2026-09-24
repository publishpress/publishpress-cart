<?php

if (! defined('ABSPATH')) {
    exit;
}


$query_args = is_array($query_args) ? $query_args : [];
$cache_key  = 'ppcart_reports_customer_lifetime_value';
if (! empty($query_args)) {
    $cache_key .= '_' . md5(wp_json_encode($query_args));
}
$cached = get_transient($cache_key);

if (is_array($cached) && isset($cached['value'], $cached['customers'], $cached['net'])) {
    return $cached;
}

$paid_args = array_merge(
    [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => 'paid',
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ],
    $query_args
);

$paid_query = new WP_Query($paid_args);

$gross_revenue = 0;
$customers     = [];

foreach ($paid_query->posts as $order_id) {
    $gross_revenue += (float) ppcart_get_post_meta($order_id, 'amount', true);
    $email          = strtolower((string) ppcart_get_post_meta($order_id, 'email', true));

    if ('' !== $email) {
        $customers[ $email ] = true;
    }
}

$refund_args = array_merge(
    [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => 'refunded',
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ],
    $query_args
);

$refund_query = new WP_Query($refund_args);

$refunded = 0;

foreach ($refund_query->posts as $order_id) {
    $refund_data = $this->get_refund_data($order_id);
    $refunded   += $refund_data['amount'];
}

wp_reset_postdata();

$customer_count = count($customers);
$net_revenue    = max(0, $gross_revenue - $refunded);

$result = [
    'value'     => $customer_count ? $net_revenue / $customer_count : 0,
    'customers' => $customer_count,
    'net'       => $net_revenue,
];

set_transient($cache_key, $result, 15 * MINUTE_IN_SECONDS);

return $result;
