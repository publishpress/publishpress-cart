<?php

if (! defined('ABSPATH')) {
    exit;
}

$fields = [
    [
        'text' => [
            'class'         => 'ppcart-unique required',
            'id'            => 'option_id',
            'label'         => __('Plan ID: ', 'publishpress-cart'),
            'type'          => 'text',
            'value'         => '',
            'class_size'    => 'one-half first',
        ],
    ],
    [
        'text' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'stripe_plan_id',
            'label'         => '',
            'placeholder'   => '',
            'type'          => 'hidden',
            'value'         => '',
            'class_size'    => 'hide',
        ],
    ],
    [
        'text' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'sale_stripe_plan_id',
            'label'         => '',
            'placeholder'   => '',
            'type'          => 'hidden',
            'value'         => '',
            'class_size'    => 'hide',
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'product_type',
            'label'         => __('Price Type', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'selections'    => [
                '' => 'One-time Payment',
                'recurring' => 'Recurring Payments',
                'pwyw'      => 'Pay What You Want',
                'free'      => 'Free',
            ],
            'class_size' => 'one-half first',
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => __('`A-z 0-9`, dashes, &amp; underscores without spaces only. Must be unique for this product.', 'publishpress-cart'),
            'id'            => 'url_slug',
            'label'         => __('URL Slug', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'text',
            'value'         => '',
            'class_size'    => 'one-half',
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat name repeater-title',
            'description'       => __("A description of this payment plan option that's displayed on the order form.", 'publishpress-cart'),
            'id'            => 'option_name',
            'label'         => __('Option Label', 'publishpress-cart'),
            'placeholder'   => __('e.g. One payment of $100', 'publishpress-cart'),
            'type'          => 'text',
            'value'         => '',
            'class_size'    => 'one-half first',
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => 'A description of this payment plan when on sale',
            'id'            => 'sale_option_name',
            'label'         => __('Sale Option Label', 'publishpress-cart'),
            'placeholder'   => 'e.g. One payment of $50 (that\'s 50% off!)',
            'type'          => 'text',
            'value'         => '',
            'class_size'    => 'one-half',
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat required',
            'description'   => '',
            'id'            => 'price',
            'label'         => __('Price', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'price',
            'value'         => '',
            'class_size'    => 'one-half first',
            'step'          => 'any',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'free',
                    'compare' => '!=',
                ],
            ],
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => '',
            'id'            => 'sale_price',
            'label'         => __('Sale Price', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'price',
            'value'         => '',
            'class_size'    => 'one-half',
            'step'          => 'any',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'free',
                    'compare' => '!=',
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'frequency',
            'label'         => __('Frequency', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'selections'    => [
                '1' => __('Every', 'publishpress-cart'),
                '2' => __('Every 2nd', 'publishpress-cart'),
                '3' => __('Every 3rd', 'publishpress-cart'),
                '4' => __('Every 4th', 'publishpress-cart'),
                '5' => __('Every 5th', 'publishpress-cart'),
                '6' => __('Every 6th', 'publishpress-cart'),
                '7' => __('Every 7th', 'publishpress-cart'),
                '8' => __('Every 8th', 'publishpress-cart'),
                '9' => __('Every 9th', 'publishpress-cart'),
                '10' => __('Every 10th', 'publishpress-cart'),
                '11' => __('Every 11th', 'publishpress-cart'),
                '12' => __('Every 12th', 'publishpress-cart'),
            ],
            'class_size' => 'one-half first',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'recurring',
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'sale_frequency',
            'label'         => __('Frequency (On Sale)', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'selections'    => [
                '1' => __('Every', 'publishpress-cart'),
                '2' => __('Every 2nd', 'publishpress-cart'),
                '3' => __('Every 3rd', 'publishpress-cart'),
                '4' => __('Every 4th', 'publishpress-cart'),
                '5' => __('Every 5th', 'publishpress-cart'),
                '6' => __('Every 6th', 'publishpress-cart'),
                '7' => __('Every 7th', 'publishpress-cart'),
                '8' => __('Every 8th', 'publishpress-cart'),
                '9' => __('Every 9th', 'publishpress-cart'),
                '10' => __('Every 10th', 'publishpress-cart'),
                '11' => __('Every 11th', 'publishpress-cart'),
                '12' => __('Every 12th', 'publishpress-cart'),
            ],
            'class_size' => 'one-half',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'recurring',
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'interval',
            'label'         => __('Pay Interval', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'selections'    => [
                'day' => __('Day', 'publishpress-cart'),
                'week' => __('Week', 'publishpress-cart'),
                'month' => __('Month', 'publishpress-cart'),
                'year' => __('Year', 'publishpress-cart'),
            ],
            'class_size' => 'one-half first',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'recurring',
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'sale_interval',
            'label'         => __('Pay Interval (On Sale)', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'selections'    => [
                'day' => __('Day', 'publishpress-cart'),
                'week' => __('Week', 'publishpress-cart'),
                'month' => __('Month', 'publishpress-cart'),
                'year' => __('Year', 'publishpress-cart'),
            ],
            'class_size' => 'one-half',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'recurring',
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'installments',
            'label'         => __('Number of Payments', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'selections'    => ($save) ? '' : $installmentsArr,
            'class_size' => 'one-half first',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'recurring',
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'sale_installments',
            'label'         => __('Number of Payments (On Sale)', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'selections'    => ($save) ? '' : $installmentsArr,
            'class_size' => 'one-half',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'recurring',
                ],
            ],
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => '',
            'id'            => 'name_your_own_price_text',
            'label'         => __('Name Your Price Label', 'publishpress-cart'),
            'placeholder'   => __('Name Your Price', 'publishpress-cart'),
            'type'          => 'text',
            'value'         => __('Name Your Price, normally $5.00', 'publishpress-cart'),
            'class_size'    => '',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'pwyw',
                ],
            ],
        ],
    ],
    [
        'checkbox' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'recurring_pwyw',
            'label'         => __('Enable Pay What You Want', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'checkbox',
            'value'         => '',
            'class_size'    => 'one-half first',
            'conditional_logic' => [
                [
                    'field' => 'product_type',
                    'value' => 'recurring',
                ],
            ],
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => '',
            'id'            => 'name_your_own_price_text_recurring',
            'label'         => __('Name Your Price Label', 'publishpress-cart'),
            'placeholder'   => __('Name Your Price', 'publishpress-cart'),
            'type'          => 'text',
            'value'         => __('Name Your Price, normally $5.00', 'publishpress-cart'),
            'class_size'    => 'one-half',
            'conditional_logic' => '',
        ],
    ],
];

return $fields;
