<?php

if (! defined('ABSPATH')) {
    exit;
}


$fields[1]['fields'][] = [
    'select' => [
        'class'         => '',
        'id'            => $this->service_name . '_forms',
        'label'         => __('Kit Forms', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'class_size'    => '',
        'selections'    => ($save) ? '' : $this->get_convertkit_form_options(),
        'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
            ]];

$fields[1]['fields'][] = [
    'select' => [
        'class'         => '',
        'id'            => $this->service_name . '_tags',
        'label'         => __('Kit Tags', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'class_size'    => '',
        'selections'    => ($save) ? '' : $this->get_converkit_tag_options(),
        'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
            ]];
$fields[1]['fields'][] = [
    'textarea' => [
        'class'         => 'field_map',
        'id'            => $this->service_name . '_field_map',
        'label'         => __('Field Map', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'textarea',
        'note'          => __('Put each field pair on a separate line and use a colon (":") to separate the Kit personalization tag from the field value. For example: %TAG%:ppcart_field_id', 'publishpress-cart'),
        'value'         => '',
        'class_size'    => '',
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
