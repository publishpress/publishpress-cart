<?php

if (! defined('ABSPATH')) {
    exit;
}


$options[$this->service_name] =  [
    $this->service_name . '-api' => [
        'type'          => 'text',
            'label'         => esc_html__('Kit API Key', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_converkit_api',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'integrations',
        ],
    $this->service_name . '-sk' => [
        'type'          => 'password',
            'label'         => esc_html__('Kit Secret Key', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_converkit_secret_key',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'integrations',
        ],
];
return $options;
