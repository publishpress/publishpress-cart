<?php

if (! defined('ABSPATH')) {
    exit;
}

$this->confirmation = [
    [
        'class'         => '',
        'description'   => __('What customers see after they pay.', 'publishpress-cart'),
        'id'            => '_ppcart_confirmation',
        'label'         => __('Confirmation type', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'selections'    =>  [
            'message'   => __('Display Message', 'publishpress-cart'),
            'page'      => __('Display Page', 'publishpress-cart'),
            'redirect'  => __('Perform Redirect', 'publishpress-cart'),
        ],
        'class_size' => '',
    ],
    [
        'class'         => 'widefat',
        'id'            => '_ppcart_confirmation_message',
        'description'   => __('Shown above the order receipt.', 'publishpress-cart'),
        'tooltip'       => __('Line breaks are kept.', 'publishpress-cart'),
        'label'         => __('Message', 'publishpress-cart'),
        'type'          => 'textarea',
        'rows'          => 4,
        'value'         => __('Thank you. We\'ve received your order.', 'publishpress-cart'),
        'class_size'        => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('confirmation'),
                'value' => 'message', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
            ],
        ],
    ],
    [
        'class'         => '',
        'description'   => __('Customers go to this page after they pay.', 'publishpress-cart'),
        'tooltip'       => __('Add [ppcart_receipt] to the page to show the order details.', 'publishpress-cart'),
        'id'            => '_ppcart_confirmation_page',
        'label'         => __('Page', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'select',
        'value'         => '',
        'selections'    => $this->option_sources->get_pages(),
        'class_size' => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('confirmation'),
                'value' => 'page',
            ],
        ],
    ],
    [
        'class'     => 'widefat',
        'description'   => __('Customers go to this URL after they pay.', 'publishpress-cart'),
        'tooltip'       => __('You can use tags such as {customer_email} in the URL.', 'publishpress-cart'),
        'id'            => '_ppcart_redirect',
        'label'     => __('Thank-you page URL', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'text',
        'value'     => '',
        'class_size'        => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('confirmation'),
                'value' => 'redirect', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
            ],
        ],
    ],
];

$this->confirmation = apply_filters('ppcart_confirmation_fields', $this->confirmation, $save);

$notification_preview_markup = '<button type="button" class="button ppcart-notif-preview-btn" data-ppcart-notif-preview data-testid="' . esc_attr(ppcart_testid('ppcart-product-notification-preview')) . '">'
    . esc_html__('Preview & send test email', 'publishpress-cart')
    . '</button>';

$this->notifications = [
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_disable_pending_email',
        'label'     => __('Order received email', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_disable_welcome_email',
        'label'     => __('New user welcome email', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_disable_purchase_email',
        'label'     => __('Purchase confirmation email', 'publishpress-cart'),
        'placeholder'   => '',
        'type'      => 'checkbox',
        'value'     => '',
        'class_size'        => '',
    ],
    [
        'class'     => 'widefat',
        'id'            => '_ppcart_notification_heading',
        'type'      => 'html',
        'value'     => '<div id="rid_ppcart_notification_heading" class="ppcart-field ppcart-row"><div class="input-group field-text"><div style="width: 100%;" "=""><h4 style="margin-bottom: 0;padding-bottom: 7px;border-bottom: 1px solid #d5d5d5;font-weight: normal;"><b>' . __('Additional Purchase Notifications', 'publishpress-cart') . '</b></h4></div></div></div>',
        'class_size' => '',
        'conditional_logic' => '',
    ],
    [
        'class'         => 'ppcart-repeater',
        'id'            => '_ppcart_notifications',
        'label-add'     => __('+ Add notification', 'publishpress-cart'),
        'label-edit'    => __('Edit notification', 'publishpress-cart'),
        'label-header'  => __('Notification', 'publishpress-cart'),
        'label-remove'  => __('Remove notification', 'publishpress-cart'),
        'title-field'   => 'notification_name',
        'type'          => 'repeater',
        'value'         => '',
        'class_size'    => '',
        'empty_state'   => [
            'icon'  => 'dashicons-email-alt',
            'title' => __('No custom notifications yet', 'publishpress-cart'),
            'text'  => __('Add one to email you, your team, or the customer when this product sells.', 'publishpress-cart'),
        ],
        'fields'        => [
            [
                'text' => [
                    'class'         => 'widefat required repeater-title',
                    'description'   => '',
                    'id'            => 'notification_name',
                    'label'         => __('Notification label', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'value'         => '',
                    'class_size'    => '',
                ],
            ],
            [
                'checkbox' => [
                    'class'         => 'ppcart-repeater-enable-input',
                    'description'   => '',
                    'id'            => 'enabled',
                    'label'         => __('Enable', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'checkbox',
                    'value'         => 1,
                    'class_size'    => 'ppcart-repeater-enable',
                    'header_toggle' => true,
                ],
            ],
            [
                'select' => [
                    'class'         => '',
                    'id'            => 'send_to',
                    'label'         => __('Send to', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'select',
                    'value'         => '',
                    'class_size'    => '',
                    'selections'    => [
                        'enter'     => __('Enter Email', 'publishpress-cart'),
                        'purchaser' => __('Purchaser Email', 'publishpress-cart'),
                        'admin'     => __('Admin Email', 'publishpress-cart'),
                    ],
                ],
            ],
            [
                'text' => [
                    'class'         => 'widefat required',
                    'description'   => '',
                    'id'            => 'send_to_email',
                    'label'         => __('Send to email', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'value'         => '',
                    'class_size'    => '',
                    'conditional_logic' => [
                        [
                            'field' => 'send_to',
                            'value' => 'enter', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        ],
                    ],
                ],
            ],
            [
                'html' => [
                    'class'         => 'widefat',
                    'description'   => '',
                    'id'            => 'notification_sender_summary',
                    'label'         => '',
                    'placeholder'   => '',
                    'type'          => 'html',
                    'value'         => '<div class="ppcart-product-notification-sender-summary"><span>' . esc_html__('Using global sender:', 'publishpress-cart') . ' ' . esc_html(ppcart_get_email_from_name()) . ' &lt;' . esc_html(ppcart_get_email_from_email()) . '&gt;</span><button type="button" class="button-link ppcart-product-notification-advanced-toggle" aria-expanded="false">' . esc_html__('Advanced sender settings', 'publishpress-cart') . '</button></div>',
                    'class_size'    => '',
                ],
            ],
            [
                'text' => [
                    'class'         => 'widefat',
                    'description'   => '',
                    'id'            => 'from_name',
                    'label'         => __('From name', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'value'         => '',
                    'class_size'    => 'ppcart-product-notification-advanced-field',
                ],
            ],
            [
                'text' => [
                    'class'         => 'widefat',
                    'description'   => '',
                    'id'            => 'from_email',
                    'label'         => __('From email', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'value'         => '',
                    'class_size'    => 'ppcart-product-notification-advanced-field',
                ],
            ],
            [
                'text' => [
                    'class'         => 'widefat',
                    'description'   => '',
                    'id'            => 'reply_to',
                    'label'         => __('Reply to', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'value'         => '',
                    'class_size'    => 'ppcart-product-notification-advanced-field',
                ],
            ],
            [
                'text' => [
                    'class'         => 'widefat',
                    'description'   => '',
                    'id'            => 'bcc',
                    'label'         => __('Bcc', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'value'         => '',
                    'class_size'    => 'ppcart-product-notification-advanced-field',
                ],
            ],
            [
                'text' => [
                    'class'         => 'widefat required',
                    'description'   => '',
                    'id'            => 'subject',
                    'label'         => __('Subject', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'value'         => '',
                    'class_size'    => '',
                ],
            ],
            [
                'editor' => [
                    'class'         => 'widefat required',
                    'description'   => '',
                    'id'            => 'message',
                    'label'         => __('Message', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'          => 'email_editor',
                    'value'         => '',
                    'show_tags'     => true,
                    'settings'      => [
                        'wpautop'       => true,
                        'textarea_rows' => 8,
                        'media_buttons' => false,
                        'teeny'         => true,
                    ],
                    'class_size'    => '',
                ],
            ],
            [
                'html' => [
                    'class'         => 'widefat',
                    'description'   => '',
                    'id'            => 'notification_preview',
                    'label'         => '',
                    'placeholder'   => '',
                    'type'          => 'html',
                    'value'         => $notification_preview_markup,
                    'class_size'    => 'ppcart-product-notification-preview-field',
                ],
            ],

        ],
    ],
];
