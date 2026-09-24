<?php

if (! defined('ABSPATH')) {
    exit;
}


return [
    [
        'checkbox' => [
            'class'     => '',
            'description'   => '',
            'id'            => 'wlm_pending',
            'label'     => __('Require admin approval', 'publishpress-cart'),
            'placeholder'   => '',
            'type'      => 'checkbox',
            'value'     => '',
            'conditional_logic' =>  [
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
            'class'         => 'mailpoet_list_name',
            'id'            => 'mailpoet_list',
            'label'         => __('MailPoet List', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_mailpoet_lists(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'mailpoet', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'checkbox' => [
            'class'     => '',
            'description'   => '',
            'id'            => 'mp_schedule_welcome',
            'label'     => __('Send welcome email', 'publishpress-cart'),
            'placeholder'   => '',
            'type'      => 'checkbox',
            'value'     => '',
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'mailpoet', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'checkbox' => [
            'class'     => '',
            'description'   => '',
            'id'            => 'mp_admin_email',
            'label'     => __('Disable admin notification', 'publishpress-cart'),
            'placeholder'   => '',
            'type'      => 'checkbox',
            'value'     => '',
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'mailpoet', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'membervault_action',
            'label'         => __('Action', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => [
                'add_user' => __('Add user', 'publishpress-cart'),
                'remove_user' => __('Remove user', 'publishpress-cart'),
            ],
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'membervault', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                ],
            ],
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => '',
            'id'            => 'member_vault_course_id',
            'label'         => __('Membervault Course ID', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'text',
            'value'         => '',
            'class_size'    => '',
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'membervault', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => 'ppcart-selectize multiple required',
            'description'   => __('Only users with one of the selected roles will be updated.', 'publishpress-cart'),
            'id'            => 'previous_user_role',
            'label'         => __('Previous User Role(s)', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_user_roles(),
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'update user', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'user_role',
            'label'         => __('New User Role', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_user_roles(),
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => apply_filters('ppcart_create_user_integrations', ['create user', 'update user', 'tutor']), // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => 'IN', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
];
