<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
if (!isset($_GET['post'])) {
    return;
}

$options = ['' => __('Any', 'publishpress-cart')];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value from current admin post.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

if ($ob_id = ppcart_get_post_meta($current_post_id, 'ob_product', true)) {
    $ob_id = intval($ob_id);
    if (!ppcart_get_post_meta($current_post_id, 'ob_replace', true)) { // don't add as an option if the bump replaces the main product
        $options['main'] =  __('Main Bump', 'publishpress-cart') . ' (' . get_the_title($ob_id) . ')';
    }
}

if ($bumps = ppcart_get_post_meta($current_post_id, 'order_bump_options', true)) {
    if (! is_array($bumps)) {
        return $options;
    }
    foreach ($bumps as $k => $bump) {
        if (isset($bump['ob_product'])) {
            $ob_id = intval($bump['ob_product']);
            /* translators: 1: bump number, 2: product title. */
            $options[$k + 1] = sprintf(__('Add\'l Bump %1$d (%2$s)', 'publishpress-cart'), $k + 1, get_the_title($ob_id));
        }
    }
}

return $options;
