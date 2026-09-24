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
        'fields'        => $this->pay_plan_fields($save),
    ],
];
