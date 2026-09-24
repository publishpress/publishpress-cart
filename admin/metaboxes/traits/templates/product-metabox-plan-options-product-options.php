<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
if (!isset($_GET['post'])) {
    return;
}

global $ppcart_public;
remove_filter('the_title', [$ppcart_public, 'public_product_name']);

$options = ['' => __('-- Select Product --', 'publishpress-cart')];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only use to exclude current product from select options.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

// The Query
$args = [
    'post_type' => ppcart_query_post_types('product'),
    // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Required to prevent self-selection in related product picker.
    'post__not_in' => [$current_post_id],
    'post_status' => 'publish',
    // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
    'posts_per_page' => -1,
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

add_filter('the_title', [$ppcart_public, 'public_product_name']);
return $options;
