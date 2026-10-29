<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    [
        'class'     => 'widefat',
        'description'   => __('Ask for the customer\'s address at checkout.', 'publishpress-cart'),
        'tooltip'       => __('Street, city, state, ZIP and country become required.', 'publishpress-cart'),
        'id'            => '_ppcart_show_address_fields',
        'label'     => __('Display address fields', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
    ],
    [
        'class'         => 'ppcart-color-field',
        'description'   => '',
        'tooltip'       => __('Colors the order button and the active step in two-step checkout.', 'publishpress-cart'),
        'id'            => '_ppcart_button_color',
        'label'         => __('Primary color', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'text',
        'value'         => '#000000',
    ],
    [
        'class'         => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_button_text',
        'label'         => __('Submit button text', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'text',
        'value'         => __('Order Now', 'publishpress-cart'),
    ],
    [
        'class'         => '',
        'description'   => '',
        'tooltip'       => __('Default: a required Terms checkbox shows when a Terms URL is set in Cart settings > Pages.', 'publishpress-cart'),
        'id'            => '_ppcart_terms_setting',
        'label'         => __('Show terms checkbox', 'publishpress-cart'),
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
        'tooltip'       => __('Default: a required Privacy checkbox shows when a Privacy Policy URL is set in Cart settings > Pages.', 'publishpress-cart'),
        'id'            => '_ppcart_privacy_setting',
        'label'         => __('Show privacy checkbox', 'publishpress-cart'),
        'type'          => 'select',
        'value'         => '',
        'selections'    => [
            ''          => __('Use default setting', 'publishpress-cart'),
            'off'       => __('Disabled', 'publishpress-cart'),
        ],
        'class_size' => '',
    ],
];
