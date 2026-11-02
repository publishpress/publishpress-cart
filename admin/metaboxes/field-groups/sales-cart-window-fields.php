<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    [
        'class'     => 'datepicker',
        'description'   => __('Leave empty to open now.', 'publishpress-cart'),
        'tooltip'       => __('Uses the site time zone.', 'publishpress-cart'),
        'id'            => '_ppcart_checkout_starts',
        'label'     => __('Checkout starts', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'text',
        'value'     => '',
        'class_size' => '',
    ],
    [
        'class'     => 'datepicker',
        'description'   => __('Leave empty to keep it open.', 'publishpress-cart'),
        'tooltip'       => __('Uses the site time zone.', 'publishpress-cart'),
        'id'            => '_ppcart_checkout_ends',
        'label'     => __('Checkout ends', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'text',
        'value'     => '',
        'class_size' => '',
    ],
    [
        'class'         => 'ppcart-segmented',
        'description'   => '',
        'id'            => '_ppcart_checkout_ended_action',
        'label'         => __('Show customers', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'selections'    => [
            'message'       => __('A message', 'publishpress-cart'),
            'redirect'      => __('Another page', 'publishpress-cart'),
        ],
        'class_size' => '',
    ],
    [
        'description'   => __('Customers go to this URL instead of the checkout form.', 'publishpress-cart'),
        'placeholder'   => 'https://',
        'id'            => '_ppcart_checkout_ended_redirect',
        'label'         => __('Redirect URL', 'publishpress-cart'),
        'value'         => '',
        'type'          => 'url',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('checkout_ended_action'),
                'value' => 'redirect',
            ],
        ],
    ],
    [
        'description'   => __('Shown in place of the checkout form.', 'publishpress-cart'),
        'id'            => '_ppcart_checkout_ended_message',
        'label'         => __('Checkout ended message', 'publishpress-cart'),
        'value'         => __('Sorry, this product is no longer for sale.', 'publishpress-cart'),
        'type'          => 'text',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('checkout_ended_action'),
                'value' => 'message',
            ],
        ],
    ],
];
