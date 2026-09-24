<?php

if (! defined('ABSPATH')) {
    exit;
}


return [
    [
        'select' => [
            'class'         => 'select service_select required repeater-title',
            'id'            => 'services',
            'label'         => __('Service', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_service_type(),
        ],
    ],
    [
        'select' => [
            'class'         => 'ppcart-selectize multiple',
            'id'            => 'service_trigger',
            'label'         => __('Trigger', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => 'one-half first',
            'selections'    => ($save) ? '' : $this->option_sources->get_trigger_option(),
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => '', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '!=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => 'ppcart-selectize multiple',
            'description'   => __('Run only if the order contains a specific payment plan (or purchase type) for this product. Leave blank to run this integration on any order for this product.', 'publishpress-cart'),
            'id'            => 'int_plan',
            'label'         => __('Restrict by payment plan / purchase type', 'publishpress-cart'),
            'placeholder'   => __('Any', 'publishpress-cart'),
            'type'          => 'select',
            'value'         => '',
            'class_size'    => 'one-half',
            'selections'    => ($save) ? '' : PPCart_Product_Metabox_Option_Sources::get_payment_plans(),
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => '', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '!=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => 'widefat',
            'description'   => '',
            'id'            => 'webhook_method',
            'label'         => __('Method', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ['get' => 'GET', 'post' => 'POST'],
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'webhook', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'text' => [
            'class'         => 'widefat',
            'description'   => '',
            'id'            => 'webhook_url',
            'label'         => __('Webhook URL', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'text',
            'value'         => '',
            'class_size'    => '',
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'webhook', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'checkbox' => [
            'class'     => '',
            'description'   => '',
            'id'            => 'hook_headers_on',
            'label'     => __('Include headers', 'publishpress-cart'),
            'placeholder'   => '',
            'type'      => 'checkbox',
            'value'     => '',
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => 'webhook', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'textarea' => [
            'class'         => 'hook_headers',
            'id'            => 'hook_headers',
            'label'         => __('Headers', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'textarea',
            'note'   => __('Put each header on a separate line', 'publishpress-cart'),
            'value'         => '',
            'class_size'    => '',
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'webhook', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
                [
                    'field' => 'hook_headers_on',
                    'value' => true,
                ],
            ],
        ],
    ],
    [
        'textarea' => [
            'class'         => 'field_map',
            'id'            => 'field_map',
            'label'         => __('Field Map', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'textarea',
            'note'   => __('Put each field pair on a separate line and use a colon (":") to separate the field key from the field value. For example: field_key:ppcart_field_id', 'publishpress-cart'),
            'value'         => '',
            'class_size'    => '',
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'webhook', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => '',
            'id'            => 'service_action',
            'label'         => __('Action', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => [
                'subscribed' => __('Add contact', 'publishpress-cart'),
                'unsubscribed' => __('Remove contact', 'publishpress-cart'),
            ],
            'conditional_logic' =>  [
                [
                    'field' => 'services',
                    'value' => apply_filters('ppcart_integration_service_action_field_logic_options', ['activecampaign', 'mailchimp', 'mailpoet', 'sendfox']), // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => 'IN', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => 'mail_chimp_list_name',
            'id'            => 'mail_list',
            'label'         => __('Mailchimp List', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_mailchimp_lists(),
            'conditional_logic' => [
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
            'class'         => 'mail_chimp_list_tags',
            'id'            => 'mail_tags',
            'label'         => __('Mailchimp Tags', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_mailchimp_tags(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'mailchimp', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
                [
                    'field' => 'service_action',
                    'value' => 'subscribed', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
    [
        'select' => [
            'class'         => 'mail_chimp_list_groups',
            'id'            => 'mail_groups',
            'label'         => __('Mailchimp Groups', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => '',
            'class_size'    => '',
            'selections'    => ($save) ? '' : $this->option_sources->get_mailchimp_groups(),
            'conditional_logic' => [
                [
                    'field' => 'services',
                    'value' => 'mailchimp', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
                [
                    'field' => 'service_action',
                    'value' => 'subscribed', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                    'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                ],
            ],
        ],
    ],
];
