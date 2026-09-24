<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    'On Sale?' => [
        'class'     => 'widefat',
        'note'  => __('Temporarily sell this product at a discounted price (overrides sale schedule)', 'publishpress-cart'),
        'id'            => '_ppcart_on_sale',
        'label'     => __('On Sale?', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    'Schedule Sale?' => [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_schedule_sale',
        'label'     => __('Schedule Sale?', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'datepicker',
        'description'   => '',
        'id'            => '_ppcart_sale_start',
        'label'     => __('Sale Start', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'text',
        'value'     => '',
        'class_size' => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('schedule_sale'),
                'value' => true,
            ],
        ],
    ],
    [
        'class'     => 'datepicker',
        'description'   => '',
        'id'            => '_ppcart_sale_end',
        'label'     => __('Sale End', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'text',
        'value'     => '',
        'class_size' => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('schedule_sale'),
                'value' => true,
            ],
        ],
    ],
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_show_full_price',
        'label'     => __('Show original price', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
];
