<?php

if (! defined('ABSPATH')) {
    exit;
}


// New products list the paths too, so no check for $_GET['post'] here.
$options = ['' => __('-- Select offers after checkout --', 'publishpress-cart')];

// The Query
$args = [
    'post_type' => function_exists('ppcart_query_pro_post_types') ? ppcart_query_pro_post_types('us_path') : [ 'ppcart_us_path' ],
    'post_status' => 'publish',
    // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- The select must list every upsell path.
    'posts_per_page' => -1,
];
$the_query = new WP_Query($args);

// The Loop
if ($the_query->have_posts()) {
    foreach ($the_query->posts as $loop_post) {
        $options[$loop_post->ID] = get_the_title($loop_post) . ' (ID: ' . $loop_post->ID . ')';
    }
} else {
    $options = false;
}

return $options;
