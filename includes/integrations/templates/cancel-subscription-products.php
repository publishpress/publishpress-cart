<?php

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin integration editor reads current post context from query parameter.
if (!isset($_GET['post'])) {
    return;
}

global $ppcart_public;
remove_filter('the_title', [ $ppcart_public, 'public_product_name' ]);

$options = ['' => __('-- Select Product --', 'publishpress-cart')];

// The Query
$args = [
    'post_type' => array_merge(ppcart_query_post_types('product'), ppcart_query_pro_post_types('collection')),
    'post_status' => 'publish',
    // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
    'posts_per_page' => -1,
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Product list must filter for plans metadata existence.
    'meta_query' => [
        'key' => ppcart_meta_key('pay_options'),
        'compare' => 'EXISTS',
    ],
];
$the_query = new WP_Query($args);

// The Loop
if ($the_query->have_posts()) {
    while ($the_query->have_posts()) {
        $the_query->the_post();
        $options[get_the_ID()] = get_the_title() . ' (ID: ' . get_the_ID() . ')';
    }
} else {
    $options = ['' => __('-- none found --', 'publishpress-cart')];
}
/* Restore original Post Data */
wp_reset_postdata();

add_filter('the_title', [ $ppcart_public, 'public_product_name' ]);
return $options;
