<?php

if (! defined('ABSPATH')) {
    exit;
}


return [
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => '',
            'id'            => 'mc_phone_tag',
            'label'         => __('Phone Merge Tag', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'text',
            'value'         => '',
            'class_size'    => '',
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'mailchimp', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'activecampaign_lists',
            'label'         => __('ActiveCampaign List', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_activecampaign_lists(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'activecampaign', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => 'Separate multiple classes with commas',
            'id'            => 'activecampaign_tags',
            'label'         => __('ActiveCampaign Tags', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'text',
            'value'         => '',
            'class_size'    => '',
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'activecampaign', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'textarea' => [
            'class'         => 'field_map',
            'id'            => 'activecampaign_field_map',
            'label'         => __('Field Map', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'textarea',
            'note'   => __('Put each field pair on a separate line and use a colon (":") to separate the ActiveCampaign personalization tag from the field value. For example: %TAG%:ppcart_field_id', 'publishpress-cart'),
            'value'         => '',
            'class_size'    => '',
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'activecampaign', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => 'sendfox_list_name',
            'id'            => 'sendfox_list',
            'label'         => __('SendFox List', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_sendfox_lists(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'sendfox', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'tutor_action',
            'label'         => __('Tutor Action', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : [
                'enroll' => __('Enroll in Course', 'publishpress-cart'),
                'cancel' => __('Cancel Enrollment', 'publishpress-cart'),
            ],
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'tutor', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'tutor_course',
            'label'         => __('Course', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->tutor_courses(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'tutor', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'wlm_action',
            'label'         => __('Member Actions', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : [
                'add'    => __('Add to Level', 'publishpress-cart'),
                'cancel' => __('Cancel from Level', 'publishpress-cart'),
                'remove' => __('Remove from Level', 'publishpress-cart'),
            ],
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'wishlist', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'wlm_level',
            'label'         => __('Membership Level', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_wlm_levels(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'wishlist', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'rcp_level',
            'label'         => __('Membership Level', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_rcp_levels(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'rcp', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'rcp_status',
            'label'         => __('Member Status', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : ['pending' => 'Pending', 'active' => 'Active', 'canceled' => 'Canceled', 'expired' => 'Expired'],
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'rcp', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'     => '',
            'description'   => '',
            'id'            => 'wlm_send_email',
            'label'     => __('Email notification', 'publishpress-cart'),
            'placeholder'   => '',
            'type'      => 'select',
            'value'     => '',
            'selections'    => ['' => __('Do not send', 'publishpress-cart'), 'level' => __('Send level notification', 'publishpress-cart'), '1' => __('Send global notification', 'publishpress-cart')],
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'wishlist', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
];
