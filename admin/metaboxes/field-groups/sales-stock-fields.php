<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_manage_stock',
        'label'     => __('Limit product sales', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'widefat required',
        'description'   => __('Total available # of this product. Once this reaches 0, the cart will close.', 'publishpress-cart'),
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
