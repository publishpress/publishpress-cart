<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
if (!isset($_GET['post'])) {
    return;
}

$options = ['' => __('-- Select Upsell Path --', 'publishpress-cart')];

// The Query
$args = [
    'post_type' => function_exists('ppcart_query_pro_post_types') ? ppcart_query_pro_post_types('us_path') : [ 'ppcart_us_path' ],
    'post_status' => 'publish',
];
$the_query = new WP_Query($args);

// The Loop
if ($the_query->have_posts()) {
    while ($the_query->have_posts()) {
        $the_query->the_post();
        $options[get_the_ID()] = get_the_title() . ' (ID: ' . get_the_ID() . ')';
    }
} else {
    $options = false;
}
/* Restore original Post Data */
wp_reset_postdata();

return $options;
