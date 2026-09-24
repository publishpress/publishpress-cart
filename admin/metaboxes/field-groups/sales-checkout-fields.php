<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_show_address_fields',
        'label'     => __('Display address fields', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
    ],
    [
        'class'         => 'ppcart-color-field',
        'description'   => '',
        'id'            => '_ppcart_button_color',
        'label'         => __('Primary Color', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'text',
        'value'         => '#000000',
    ],
    [
        'class'         => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_button_text',
        'label'         => __('Submit Button Text', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'text',
        'value'         => __('Order Now', 'publishpress-cart'),
    ],
    [
        'class'         => '',
        'description'   => '',
        'id'            => '_ppcart_terms_setting',
        'label'         => __('Show Terms checkbox', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'selections'    => [
            ''          => __('Use default setting', 'publishpress-cart'),
            'off'       => __('Disabled', 'publishpress-cart'),
        ],
        'class_size' => '',
    ],
    [
        'class'         => '',
        'description'   => '',
        'id'            => '_ppcart_privacy_setting',
        'label'         => __('Show Privacy checkbox', 'publishpress-cart'),
        'type'          => 'select',
        'value'         => '',
        'selections'    => [
            ''          => __('Use default setting', 'publishpress-cart'),
            'off'       => __('Disabled', 'publishpress-cart'),
        ],
        'class_size' => '',
    ],
];
