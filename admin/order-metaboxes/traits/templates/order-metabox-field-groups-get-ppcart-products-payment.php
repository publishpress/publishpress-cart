<?php

if (! defined('ABSPATH')) {
    exit;
}


$options = ['' => 'Select Product Payment'];
$args = [
    // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_numberposts -- This admin selector intentionally loads all matching records for option lists.
    'numberposts'   => -1,
    'post_type'     => ppcart_query_post_types('product'),
    'orderby'       => 'title',
    'order'         => 'ASC',
    'post_status'   => 'publish',
];
// Get the posts
$myProducts = get_posts($args);

if ($myProducts) {
    foreach ($myProducts as $product) {
        $pay_options = ppcart_get_post_meta($product->ID, 'pay_options', true);

        if (! is_array($pay_options)) {
            continue;
        }

        foreach ($pay_options as $value) {
            if (! is_array($value) || ! isset($value['option_id'])) {
                continue;
            }

            $value['option_name'] ??= $value['option_id'];
            $options[$value['option_id']] = $value['option_name'];
            if (isset($value['sale_price'])) {
                $value['sale_option_name'] ??= $value['option_name'] . ' (on sale)';
                $options[$value['option_id'] . '_sale'] = $value['sale_option_name'];
            }
        }
    }
    wp_reset_postdata();
}
return $options;
