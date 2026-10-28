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
        'class'         => '',
        'description'   => __('What customers see while checkout is closed.', 'publishpress-cart'),
        'tooltip'       => __('Checkout is closed before it opens, after it ends and when stock is sold out.', 'publishpress-cart'),
        'id'            => '_ppcart_checkout_ended_action',
        'label'         => __('When checkout ended', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'selections'    => [
            'message'       => 'Display message',
            'redirect'      => 'Perform redirect',
        ],
        'class_size' => '',
    ],
    [
        'description'   => '',
        'id'            => '_ppcart_checkout_ended_redirect',
        'label'         => __('Checkout ended redirect URL', 'publishpress-cart'),
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
