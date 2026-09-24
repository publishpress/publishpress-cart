<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_customer_purchase_limit',
        'label'     => __('Limit sales per customer', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'widefat',
        'description'   => __('Enter the maximum amount of times a single customer can purchase this product.', 'publishpress-cart'),
        'id'            => '_ppcart_customer_limit',
        'label'     => __('Customer purchase limit', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'number',
        'value'     => '1',
        'class_size'        => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('customer_purchase_limit'),
                'value' => true,
            ],
        ],
    ],
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_customer_limit_message',
        'label'     => __('Limit reached message', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'text',
        'value'     => __('Sorry, you have already purchased this product!', 'publishpress-cart'),
        'class_size'        => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('customer_purchase_limit'),
                'value' => true,
            ],
        ],
    ],
];
