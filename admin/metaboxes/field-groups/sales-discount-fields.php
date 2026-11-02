<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    'On Sale?' => [
        'class'     => 'widefat',
        'description'   => __('Use the sale price now.', 'publishpress-cart'),
        'tooltip'       => __('While this is on, the sale dates below are not used.', 'publishpress-cart'),
        'id'            => '_ppcart_on_sale',
        'label'     => __('On sale now', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    'Schedule Sale?' => [
        'class'     => 'widefat',
        'description'   => __('Use the sale price only between two dates.', 'publishpress-cart'),
        'id'            => '_ppcart_schedule_sale',
        'label'     => __('Schedule a sale', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'datepicker',
        'description'   => __('Leave empty to start now.', 'publishpress-cart'),
        'tooltip'       => __('Uses the site time zone.', 'publishpress-cart'),
        'id'            => '_ppcart_sale_start',
        'label'     => __('Sale starts', 'publishpress-cart'),
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
        'description'   => __('Leave empty for no end date.', 'publishpress-cart'),
        'tooltip'       => __('Uses the site time zone.', 'publishpress-cart'),
        'id'            => '_ppcart_sale_end',
        'label'     => __('Sale ends', 'publishpress-cart'),
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
        'description'   => __('Show the regular price crossed out during a sale.', 'publishpress-cart'),
        'id'            => '_ppcart_show_full_price',
        'label'     => __('Show original price', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
];
