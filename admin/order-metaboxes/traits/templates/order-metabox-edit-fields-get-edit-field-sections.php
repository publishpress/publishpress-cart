<?php

if (! defined('ABSPATH')) {
    exit;
}


if (ppcart_is_order_post_type($post_type)) {
    return [
        '_ppcart_firstname'    => [
            'slug'  => 'customer',
            'title' => __('Customer', 'publishpress-cart'),
        ],
        '_ppcart_phone'        => [
            'slug'  => 'billing',
            'title' => __('Billing Address', 'publishpress-cart'),
        ],
        '_ppcart_status'       => [
            'slug'  => 'order',
            'title' => __('Order Details', 'publishpress-cart'),
        ],
    ];
}

if (ppcart_is_subscription_post_type($post_type)) {
    return [
        '_ppcart_firstname'    => [
            'slug'  => 'customer',
            'title' => __('Customer', 'publishpress-cart'),
        ],
        '_ppcart_phone'        => [
            'slug'  => 'billing',
            'title' => __('Billing Address', 'publishpress-cart'),
        ],
    ];
}

return [];
