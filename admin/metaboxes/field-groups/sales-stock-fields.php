<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    [
        'class'     => 'widefat',
        'description'   => __('Sell a limited number.', 'publishpress-cart'),
        'id'            => '_ppcart_manage_stock',
        'label'     => __('Limit product sales', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'widefat required',
        'description'   => '',
        'tooltip'       => __('Checkout closes when this reaches 0.', 'publishpress-cart'),
        'id'            => '_ppcart_limit',
        'label'     => __('Amount Remaining', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'number',
        'value'     => '',
        'class_size'        => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('manage_stock'),
                'value' => true,
            ],
        ],
    ],
];
