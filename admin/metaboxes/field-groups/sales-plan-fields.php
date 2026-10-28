<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    'Hide Plans Section' => [
        'class'         => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_hide_plans',
        'label'         => __('Hide Plans Section', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'checkbox',
        'value'         => '',
        'class_size'    => '',
    ],
    'Section Heading' => [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_plan_heading',
        'label'     => __('Section Heading', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'text',
        'value'     => __('Payment Plan', 'publishpress-cart'),
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('hide_plans'),
                'value' => true,
                'compare' => '!=',
            ],
        ],
    ],
    'Payment Plan' => [
        'class'         => 'ppcart-repeater',
        'id'            => '_ppcart_pay_options',
        'label-add'     => __('+ Add New', 'publishpress-cart'),
        'label-edit'    => __('Edit Payment Plan', 'publishpress-cart'),
        'label-header'  => __('Payment Plan', 'publishpress-cart'),
        'label-remove'  => __('Remove Payment Plan', 'publishpress-cart'),
        'title-field'   => 'name',
        'type'          => 'repeater',
        'value'         => '',
        'class_size'    => '',
        'summary'       => true,
        'sections'      => apply_filters(
            'ppcart_pay_plan_field_sections',
            [
                'main' => [
                    'fields' => [ 'product_type', 'price', 'name_your_own_price_text', 'recurring_pwyw', 'name_your_own_price_text_recurring', 'option_name' ],
                ],
                'billing' => [
                    'title'  => __('Billing', 'publishpress-cart'),
                    'fields' => [ 'frequency', 'interval', 'installments' ],
                ],
                'sale' => [
                    'title'  => __('Sale price', 'publishpress-cart'),
                    'fields' => [ 'sale_price', 'sale_frequency', 'sale_interval', 'sale_installments', 'sale_sign_up_fee', 'sale_option_name' ],
                ],
                'advanced' => [
                    'title'  => __('Advanced', 'publishpress-cart'),
                    'type'   => 'details',
                    'fields' => [ 'option_id', 'url_slug', 'is_hidden', 'stripe_plan_id', 'sale_stripe_plan_id' ],
                ],
            ]
        ),
        'fields'        => $this->pay_plan_fields($save),
    ],
];
