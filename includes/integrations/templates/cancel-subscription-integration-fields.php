<?php

if (!defined('ABSPATH')) {
    exit;
}


$fields[1]['fields'][] = [
    'select' => [
        'class'         => 'widefat update-plan-product recurring',
        'id'            => 'ppcart_sub_prod_id',
        'label'         => esc_html__('Select Product', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'class_size'    => ' one-half first',
        'selections'    => ($save) ? '' : $this->get_products(),
        'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
        ],
    ],
];

$fields[1]['fields'][] = [
    'select' => [
        'class'         => 'widefat update-plan ob-{val}',
        'id'            => 'ppcart_sub_plan_id',
        'label'         => esc_html__('Select Plan', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'class_size'    => ' one-half',
        'selections'    => $this->get_plans('ppcart_sub_prod_id'),
        'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
        ],
    ],
];

$fields[1]['fields'][] = [
    'select' => [
        'class'         => 'widefat',
        'id'            => 'ppcart_sub_cancel',
        'label'         => esc_html__('Cancel', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'class_size'    => '',
        'selections'    => ['yes' => __('Immediately', 'publishpress-cart'), 'no' => __('At the end of the current billing period', 'publishpress-cart')],
        'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
        ],
    ],
];

return $fields;
