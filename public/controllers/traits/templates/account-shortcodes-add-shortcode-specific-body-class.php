<?php

if (! defined('ABSPATH')) {
    exit;
}


global $post;

$has_account_block = false;

if (is_a($post, 'WP_Post') && function_exists('has_block')) {
    $account_blocks = [
        'publishpress-cart/account-page-builder',
        'publishpress-cart/account-tab',
        'publishpress-cart/account-navigation',
        'publishpress-cart/account-orders',
        'publishpress-cart/account-subscriptions',
        'publishpress-cart/account-payment-plans',
        'publishpress-cart/account-profile',
        'publishpress-cart/account-login',
        'publishpress-cart/account-downloads',
    ];

    foreach ($account_blocks as $account_block) {
        if (has_block($account_block, $post)) {
            $has_account_block = true;
            break;
        }
    }
}

$has_account_shortcode = false;
if (is_a($post, 'WP_Post')) {
    $has_account_shortcode = has_shortcode($post->post_content, 'ppcart_account');
}

if ($has_account_shortcode || $has_account_block) {
    $classes[] = 'account-page';
}

return $classes;
