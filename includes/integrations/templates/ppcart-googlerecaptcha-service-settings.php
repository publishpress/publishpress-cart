<?php

if (! defined('ABSPATH')) {
    exit;
}


$options[$this->service_name . '-v2'] = [
    $this->service_name . '-v2captcha_type' => [
        'class'         => 'wide-fat',
        'type'          => 'select',
        'label'         => esc_html__('Captcha Type', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_' . $this->service_name . 'v2_captcha_type',
            'value'         => '',
            'selections'    => [
                'norobo' => esc_html__('Checkbox', 'publishpress-cart'),
                'invisible' => esc_html__('Invisible', 'publishpress-cart'),
            ],
            'description'   => '',
        ],
        'tab' => 'integrations',
    ],

    $this->service_name . '-v2site_key' => [
        'type'          => 'text',
        'label'         => esc_html__('Site Key', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_' . $this->service_name . 'v2_site_key',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'integrations',
    ],

    $this->service_name . '-v2site_secret' => [
        'type'          => 'password',
        'label'         => esc_html__('Site Secret', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_' . $this->service_name . 'v2_site_secret',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'integrations',
    ],
];

$options[$this->service_name . '-v3'] = [
    $this->service_name . '-v3site_key' => [
        'type'          => 'text',
        'label'         => esc_html__('Site Key', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_' . $this->service_name . 'v3_site_key',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'integrations',
    ],

    $this->service_name . '-v3site_secret' => [
        'type'          => 'password',
        'label'         => esc_html__('Site Secret', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_' . $this->service_name . 'v3_site_secret',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'integrations',
    ],

    $this->service_name . '-v3ratings' => [
        'class'         => 'wide-fat',
        'type'          => 'select',
        'label'         => esc_html__('Minimum Score', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_' . $this->service_name . '_v3rating',
            'value'         => '0.5',
            'selections'    => [
                '0.0' => esc_html__('0.0 (Not Recommended)', 'publishpress-cart'),
                '0.1' => esc_html__('0.1 (Bots)', 'publishpress-cart'),
                '0.2' => esc_html__('0.2 (Spam)', 'publishpress-cart'),
                '0.3' => esc_html__('0.3 (Likely human)', 'publishpress-cart'),
                '0.4' => esc_html__('0.4 (Most probably human)', 'publishpress-cart'),
                '0.5' => esc_html__('0.5 (Recommended)', 'publishpress-cart'),
                '0.6' => esc_html__('0.6 (OK)', 'publishpress-cart'),
                '0.7' => esc_html__('0.7 (Good)', 'publishpress-cart'),
                '0.8' => esc_html__('0.8 (Better)', 'publishpress-cart'),
                '0.9' => esc_html__('0.9 (Best)', 'publishpress-cart'),
                '1.0' => esc_html__('1.0 (Most Recommended)', 'publishpress-cart'),
            ],
            'description'   => '',
        ],
        'tab' => 'integrations',
    ],
];
return $options;
