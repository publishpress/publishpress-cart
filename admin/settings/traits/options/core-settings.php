<?php

if (! defined('ABSPATH')) {
    exit;
}

$options = [
    'settings' => [
        'currency' => [
            'type'          => 'select',
            'label'         => esc_html__('Currency', 'publishpress-cart'),
            'subsection'    => 'currency',
            'settings'      => [
                'id'            => '_ppcart_currency',
                'value'         => 'USD',
                'selections'    => $currencies,
            ],
        ],
        'country' => [
            'type'          => 'select',
            'label'         => esc_html__('Default Country', 'publishpress-cart'),
            'subsection'    => 'currency',
            'settings'      => [
                'id'            => '_ppcart_country',
                'value'         => 'US',
                'selections'    => ppcart_countries_list(),
            ],
        ],
        'currency-position' => [
            'type'          => 'select',
            'label'         => esc_html__('Currency Position', 'publishpress-cart'),
            'subsection'    => 'currency',
            'settings'      => [
                'id'            => '_ppcart_currency_position',
                'value'         => 'USD',
                'selections'    => ['' => 'Left', 'right' => 'Right', 'left-space' => 'Left with space', 'right-space' => 'Right with space'],
            ],
        ],
        'thousand' => [
            'type'          => 'text',
            'label'         => esc_html__('Thousand Separator', 'publishpress-cart'),
            'subsection'    => 'currency',
            'settings'      => [
                'id'            => '_ppcart_thousand_separator',
                'value'         => ',',
                'class'         => 'small-text ppcart-settings__separator-input',
                'description'   => '',
            ],
        ],
        'decimal' => [
            'type'          => 'text',
            'label'         => esc_html__('Decimal Separator', 'publishpress-cart'),
            'subsection'    => 'currency',
            'settings'      => [
                'id'            => '_ppcart_decimal_separator',
                'value'         => '.',
                'class'         => 'small-text ppcart-settings__separator-input',
                'description'   => '',
            ],
        ],
        'decimal_num' => [
            'type'          => 'text',
            'label'         => esc_html__('Number of Decimals', 'publishpress-cart'),
            'subsection'    => 'currency',
            'settings'      => [
                'id'            => '_ppcart_decimal_number',
                'value'         => intval(get_option('_ppcart_decimal_number')),
                'class'         => 'small-text ppcart-settings__number-input',
                'description'   => esc_html__('The number of decimal points shown in prices.', 'publishpress-cart'),
            ],
        ],
        'my-account' => [
            'type'          => 'select',
            'label'         => esc_html__('My Account Page', 'publishpress-cart'),
            'subsection'    => 'pages',
            'settings'      => [
                'id'            => '_ppcart_myaccount_page_id',
                'value'         => '',
                'selections'    => PPCart_Admin_Settings::get_pages(),
            ],
        ],
        'my-account-hide-free' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Hide free orders in My Account', 'publishpress-cart'),
            'subsection'    => 'pages',
            'settings'      => [
                'id'            => '_ppcart_myaccount_hide_free',
                'value'         => '',
                'description'   => esc_html__('Turn on to hide free orders from the "Orders" tab in My Account', 'publishpress-cart'),
            ],
        ],
        'terms-url' => [
            'type'          => 'text',
            'label'         => esc_html__('Terms and Conditions URL', 'publishpress-cart'),
            'subsection'    => 'pages',
            'settings'      => [
                'id'            => '_ppcart_terms_url',
                'value'         => '',
                'description'   => '',
            ],
        ],
        'privacy-url' => [
            'type'          => 'text',
            'label'         => esc_html__('Privacy Policy URL', 'publishpress-cart'),
            'subsection'    => 'pages',
            'settings'      => [
                'id'            => '_ppcart_privacy_url',
                'value'         => '',
                'description'   => '',
            ],
        ],
        'company_name' => [
            'type'          => 'text',
            'label'         => esc_html__('Company Name', 'publishpress-cart'),
            'subsection'    => 'company',
            'settings'      => [
                'id'            => '_ppcart_company_name',
                'value'         => '',
                'description'   => '',
            ],
        ],
        'company_address' => [
            'type'          => 'textarea',
            'label'         => esc_html__('Company Address', 'publishpress-cart'),
            'subsection'    => 'company',
            'settings'      => [
                'id'            => '_ppcart_company_address',
                'value'         => '',
                'description'   => '',
            ],
        ],
        'company_logo' => [
            'type'          => 'upload',
            'label'         => esc_html__('Logo Image', 'publishpress-cart'),
            'subsection'    => 'company',
            'settings'      => [
                'id'            => '_ppcart_company_logo',
                'value'         => '',
                'description'   => '',
                'field-type'    => 'url',
                'label-remove'  => __('Remove Image', 'publishpress-cart'),
                'label-upload'  => __('Set Image', 'publishpress-cart'),
            ],
        ],
        'product-duplicate-enable' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable product duplicate action', 'publishpress-cart'),
            'settings'      => [
                'id'            => PPCart_Product_Duplicator::OPTION_NAME,
                'value'         => 1,
                'description'   => esc_html__('Show the Duplicate row action for products in wp-admin.', 'publishpress-cart'),
            ],
        ],
    ],
    'email_reports' => [
        'schedule-email' => [
            'type'          => 'text',
            'label'         => esc_html__('Email reports and confirmations to', 'publishpress-cart'),
            'settings'      => [
                'id'            => 'ppcart_admin_email',
                'placeholder'   => esc_html__('admin@example.com, another@example.com', 'publishpress-cart'),
                'description'   => esc_html__('Recipients for sales reports and order confirmations. Comma-separated.', 'publishpress-cart'),
                'required'      => true,
            ],
            'tab' => 'email',
        ],
        'schedule-event' => [
            'type'          => 'select',
            'label'         => esc_html__('Email Report Schedule', 'publishpress-cart'),
            'settings'      => [
                'id'            => 'ppcart_report_schedule',
                'selections'    => [
                    'ppcart_none'         => __('None', 'publishpress-cart'),
                    'ppcart_daily'        => __('Daily', 'publishpress-cart'),
                    'ppcart_weekly'       => __('Weekly', 'publishpress-cart'),
                    'ppcart_semi_monthly' => __('Semi monthly', 'publishpress-cart'),
                ],
                'description'   => esc_html__('How often summary reports should be emailed.', 'publishpress-cart'),
            ],
            'tab' => 'email',
        ],
    ],
    'email_settings' => [
        'email_from_name' => [
            'type'          => 'text',
            'label'         => esc_html__('"From" Name', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_from_name',
                'value'         => get_bloginfo('name'),
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'email_from_email' => [
            'type'          => 'text',
            'label'         => esc_html__('"From" Email', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_from_email',
                'value'         => get_bloginfo('admin_email'),
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'email_reply_to' => [
            'type'          => 'text',
            'label'         => esc_html__('Reply-To Email', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_reply_to',
                'value'         => '',
                'placeholder'   => get_bloginfo('admin_email'),
                'description'   => esc_html__('Optional reply address used by transactional emails and inherited by product notifications.', 'publishpress-cart'),
            ],
            'tab' => 'email',
        ],
        'email_footer_text' => [
            'type'          => 'textarea',
            'label'         => esc_html__('Footer text', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_footer_text',
                'value'         => get_bloginfo('name'),
                'description'   => '',
                'rows' => 2,
                'columns' => 6,
            ],
            'tab' => 'email',
        ],
        'email_preview' => [
            'type'          => 'html',
            'label'         => esc_html__('Test email', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_preview',
                'skip_register' => true,
                'description'   => '
                        <div class="ppcart-settings__email-test">
                            <div class="ppcart-settings__email-test-controls">
                                <select id="ppcart-email-type" data-testid="ppcart-admin-email-preview-type">
                                    <option value="confirmation">' . esc_html__('-- Select --', 'publishpress-cart') . '</option>
                                    <option value="pending">' . esc_html__('Order Received', 'publishpress-cart') . '</option>
                                    <option value="registration">' . esc_html__('New User Welcome', 'publishpress-cart') . '</option>
                                    <option value="confirmation">' . esc_html__('Purchase Confirmation', 'publishpress-cart') . '</option>
                                    <option value="refunded">' . esc_html__('Order Refunded', 'publishpress-cart') . '</option>
                                    <option value="trial_ending">' . esc_html__('Trial Ending Reminder', 'publishpress-cart') . '</option>
                                    <option value="reminder">' . esc_html__('Upcoming Renewal Reminder', 'publishpress-cart') . '</option>
                                    <option value="renewal">' . esc_html__('Subscription Renewal Confirmation', 'publishpress-cart') . '</option>
                                    <option value="failed">' . esc_html__('Failed Renewal Payment', 'publishpress-cart') . '</option>
                                    <option value="canceled">' . esc_html__('Subscription Canceled Confirmation', 'publishpress-cart') . '</option>
                                    <option value="paused">' . esc_html__('Subscription Paused Confirmation', 'publishpress-cart') . '</option>
                                </select>
                                <a id="ppcart-preview-email" class="button" href="' . site_url('/?ppcart-preview=email&type=[confirmation]&_wpnonce=' . wp_create_nonce('ppcart_cart')) . '" target="_blank" data-testid="ppcart-admin-email-preview-open">' . esc_html__('Preview Email', 'publishpress-cart') . '</a>
                                <a id="ppcart-email-send" class="button" href="#" data-testid="ppcart-admin-email-preview-send">' . esc_html__('Send Test', 'publishpress-cart') . '</a>
                            </div>
                            <p class="description">' . esc_html__('Preview and test emails use sample order data for personalization tags.', 'publishpress-cart') . '</p>
                        </div>
                        ',
            ],
            'tab' => 'email',
        ],
    ],
    'emailtemplate_pending' => [
        'order_pending_enable' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_pending_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'order_pending_subject' => [
            'type'          => 'text',
            'label'         => esc_html__('Subject', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_pending_subject',
                'value'         => 'Your order received confirmation from {site_name}',
                'description'   => '',
                'placeholder'   => '',
            ],
            'tab' => 'email',
        ],
        'order_pending_email_admin' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Send to admin?', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_pending_admin',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'order_pending_headline' => [
            'type'          => 'text',
            'label'         => esc_html__('Headline', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_pending_headline',
                'value'         => '',
                'placeholder'   => 'Your order has been received!',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'order_pending_body' => [
            'type'          => 'editor',
            'label'         => esc_html__('Body Text', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_pending_body',
                'value'         => '',
                'placeholder'   => '',
                'description'   => '',
                'show_tags'     => true,
                'rows'          => 2,

            ],
            'tab' => 'email',
        ],
    ],
    'emailtemplate_purchase' => [
        'purchase_confirmation_enable' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_confirmation_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'purchase_confirmation_subject' => [
            'type'          => 'text',
            'label'         => esc_html__('Subject', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_confirmation_subject',
                'value'         => 'Your order confirmation from {site_name}',
                'description'   => '',
                'placeholder'   => '',
            ],
            'tab' => 'email',
        ],
        'purchase_confirmation_email_admin' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Send to admin?', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_confirmation_admin',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'purchase_confirmation_headline' => [
            'type'          => 'text',
            'label'         => esc_html__('Headline', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_confirmation_headline',
                'value'         => '',
                'placeholder'   => 'Thank you for your order!',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'purchase_confirmation_body' => [
            'type'          => 'editor',
            'label'         => esc_html__('Body Text', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_confirmation_body',
                'value'         => '',
                'placeholder'   => '',
                'description'   => '',
                'show_tags'     => true,
                'rows'          => 2,

            ],
            'tab' => 'email',
        ],
    ],
    'emailtemplate_registration' => [
        'subscription_registration_enable' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_registration_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'use_wp_notification' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Use WordPress Default Notification', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_use_wp_notification',
                'value'         => '',
                'description'   => esc_html__('If enabled, WordPress default new user notification will be used instead of custom email template.', 'publishpress-cart'),
            ],
            'tab' => 'email',
        ],
        'subscription_registration_subject' => [
            'type'          => 'text',
            'label'         => esc_html__('Subject', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_registration_subject',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_registration_email_admin' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Send to admin?', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_registration_email_admin',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_registration_body' => [
            'type'          => 'editor',
            'label'         => esc_html__('Body', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_registration_email_body',
                'value'         => '',
                'show_tags'     => true,
                'description'   => '',
            ],
            'tab' => 'email',
        ],
    ],
    'emailtemplate_refunded' => [
        'order_refund_notification' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable/Disable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_refunded_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'order_refund_subject' => [
            'type'          => 'text',
            'label'         => esc_html__('Subject', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_refunded_subject',
                'value'         => 'Refund from {site_name}',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'order_refund_email_admin' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Send to admin?', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_refunded_admin',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'order_refund_body' => [
            'type'          => 'editor',
            'label'         => esc_html__('Body', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_refunded_body',
                'value'         => "Order ID: {order_id}\nOrder Date: {order_date}\n\nYou're receiving this email because we have processed your refund of {last_refund_amount}. It can take up to 10 days to appear on your statement, if it takes longer please contact your bank for assistance.",
                'description'   => '',
                'show_tags'     => true,
            ],
            'tab' => 'email',
        ],
    ],
    'emailtemplate_renewal' => [
        'subscription_renewal_notification' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable/Disable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_renewal_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_renewal_subject' => [
            'type'          => 'text',
            'label'         => esc_html__('Subject', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_renewal_subject',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_renewal_email_admin' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Send to admin?', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_renewal_admin',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_renewal_body' => [
            'type'          => 'editor',
            'label'         => esc_html__('Body', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_renewal_body',
                'value'         => '',
                'description'   => '',
                'show_tags'     => true,
            ],
            'tab' => 'email',
        ],
    ],
    'emailtemplate_failed' => [
        'subscription_failed_notification' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable/Disable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_failed_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_failed_subject' => [
            'type'          => 'text',
            'label'         => esc_html__('Subject', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_failed_subject',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_failed_email_admin' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Send to admin?', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_failed_admin',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_failed_email' => [
            'type'          => 'editor',
            'label'         => esc_html__('Body', 'publishpress-cart'),
            'settings'      => [
                'id'            => "_ppcart_email_failed_body",
                'value'         => '',
                'show_tags'     => true,
                'description'   => '',
            ],
            'tab' => 'email',
        ],
    ],
    'emailtemplate_canceled' => [
        'subscription_canceled_notification' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable/Disable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_canceled_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_canceled_subject' => [
            'type'          => 'text',
            'label'         => esc_html__('Subject', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_canceled_subject',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_canceled_email_admin' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Send to admin?', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_email_canceled_admin',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'email',
        ],
        'subscription_canceled_email' => [
            'type'          => 'editor',
            'label'         => esc_html__('Subscription Canceled', 'publishpress-cart'),
            'settings'      => [
                'id'            => "_ppcart_email_canceled_body",
                'value'         => '',
                'show_tags'     => true,
                'description'   => '',
            ],
            'tab' => 'email',
        ],
    ],
];

return $options;
