<?php

if (! defined('ABSPATH')) {
    exit;
}


return [
    [
        'class_size'    => '',
        'description'   => __('Add an opt-in checkbox at checkout.', 'publishpress-cart'),
        'tooltip'       => __('Customers join a mailing list only if they tick it.', 'publishpress-cart'),
        'id'            => '_ppcart_show_optin_cb',
        'label'         => __('Show opt-in checkbox', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'checkbox',
        'value'         => '',
    ],
    [
        'class_size'    => 'one-half first',
        'class'         => 'widefat',
        'description'   => __('Text beside the opt-in checkbox.', 'publishpress-cart'),
        'id'            => '_ppcart_optin_checkbox_text',
        'label'         => __('Opt-in checkbox label', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'text',
        'value'         => __('Sign me up for the newsletter', 'publishpress-cart'),
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('show_optin_cb'),
                'value' => true,
            ],
        ],
    ],
    [
        'class_size'    => 'one-half ',
        'description'   => __('Customers must tick it to place the order.', 'publishpress-cart'),
        'id'            => '_ppcart_optin_required',
        'label'         => __('Make opt-in checkbox required', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'checkbox',
        'value'         => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('show_optin_cb'),
                'value' => true,
            ],
        ],
    ],
    // Empty: the tab intro already says this. The item stays so code that edits
    // this list by position still finds the fields where it expects them.
    [
        'type'          => 'html',
        'value'         => '',
    ],
];
