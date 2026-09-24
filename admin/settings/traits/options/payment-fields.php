<?php

if (! defined('ABSPATH')) {
    exit;
}

$payment_fields = [
    'cashondelivery' => [
        'cashondelivery-gateway' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_cashondelivery_enable',
                'value'         => '1',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
    ],
    'stripe' => [
        'stripe-gateway' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'stripe-api' => [
            'type'          => 'select',
            'label'         => esc_html__('API', 'publishpress-cart'),
            'settings'      => [
                /* translators: %s: Stripe subscriptions documentation URL. */
                'note'   => sprintf(esc_html__('GOING LIVE? Click the "Update" button on any subscription products created on the Test API after switching this setting to Live!<br><a href="%s" target="_blank" rel="noopener noreferrer">More Information</a>', 'publishpress-cart'), apply_filters('ppcart_stripe_subscriptions_documentation_url', PPCART_DOCS_URL . 'subscriptions/using-stripe-with-recurring-payment-plans')),
                'id'            => '_ppcart_stripe_api',
                'value'         => 'test',
                'selections'    => [
                    'test' => __('Test', 'publishpress-cart'),
                    'live' => __('Live', 'publishpress-cart'),
                ],
            ],
            'tab' => 'payment',
        ],
        'stripe-connect-status' => [
            'type'          => 'html',
            'label'         => esc_html__('Connection Status', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_connect_status',
                'description'   => $this->stripe_connect->get_stripe_connect_settings_html(),
            ],
            'tab' => 'payment',
        ],
        'stripe-checkout-experience' => [
            'type'          => 'stripe_checkout_experience',
            'label'         => esc_html__('Checkout experience', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_checkout_experience',
                'skip_register' => true,
            ],
            'tab' => 'payment',
        ],
        'stripe-express-payment' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Express Payment', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_express_payment_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'stripe-customer-portal' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Customer Portal', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_customer_portal_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        // Stored via the Checkout experience radio; kept registered so their option pair still saves.
        'stripe-hosted-checkout' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Hosted Checkout', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_hosted_checkout_enable',
                'value'         => '',
                'show_in_ui'    => false,
            ],
            'tab' => 'payment',
        ],
        'stripe-payment-element' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Payment Element', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_payment_element_enable',
                'value'         => '1',
                'show_in_ui'    => false,
            ],
            'tab' => 'payment',
        ],
        'stripe-descriptor' => [
            'type'          => 'text',
            'label'         => esc_html__('Statement Descriptor', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_descriptor',
                'value'         => get_bloginfo('name'),
                'note'   => esc_html__('Required. 22 character maximum, no special characters', 'publishpress-cart'),
                'show_in_ui'    => false,
            ],
            'tab' => 'payment',
        ],
        'stripe-inv-webhook-processing' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Update subscription payment status in webhook only', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_stripe_invoice_webhook_updates_only',
                'value'         => '',
                'description'   => '',
                'show_in_ui'    => false,
            ],
            'tab' => 'payment',
        ],
    ],
    'paypal' => [
        'paypal-gateway' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Enable', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_enable',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-sandbox-enable' => [
            'type'          => 'select',
            'label'         => esc_html__('Enable Sandbox', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_enable_sandbox',
                'value'         => 'test',
                'selections'    => [
                    'enable' => __('Yes', 'publishpress-cart'),
                    'disable'  => __('No', 'publishpress-cart'),
                ],
            ],
            'tab' => 'payment',
        ],
        'paypal-ssl-verify' => [
            'type'          => 'checkbox',
            'label'         => esc_html__('Verify SSL certificates', 'publishpress-cart'),
            'settings'      => [
                'id'            => $paypal_ssl_verify_option,
                'value'         => $paypal_ssl_verify_value,
                'description'   => esc_html__('Verifies PayPal TLS certificates and hostname matches for server-side PayPal API requests.', 'publishpress-cart'),
                'note'          => $paypal_ssl_verify_note,
                'disabled'      => $paypal_ssl_verify_locked,
                'force_value'   => $paypal_ssl_verify_locked,
                'skip_register' => $paypal_ssl_verify_locked,
            ],
            'tab' => 'payment',
        ],
        'paypal-email' => [
            'type'          => 'text',
            'label'         => esc_html__('PayPal Email', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_email',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-client-id' => [
            'type'          => 'text',
            'label'         => esc_html__('Client ID', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_client_id',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-secret' => [
            'type'          => 'password',
            'label'         => esc_html__('Secret', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_secret',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-pdt-token' => [
            'type'          => 'password',
            'label'         => esc_html__('PDT Token', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_pdt_token',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-sandbox-email' => [
            'type'          => 'text',
            'label'         => esc_html__('Sandbox Email', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_sandbox_email',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-sandbox-client-id' => [
            'type'          => 'text',
            'label'         => esc_html__('Sandbox Client ID', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_sandbox_client_id',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-sandbox-secret' => [
            'type'          => 'password',
            'label'         => esc_html__('Sandbox Secret', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_sandbox_secret',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
        'paypal-sandbox-pdt-token' => [
            'type'          => 'password',
            'label'         => esc_html__('Sandbox PDT Token', 'publishpress-cart'),
            'settings'      => [
                'id'            => '_ppcart_paypal_sandbox_pdt_token',
                'value'         => '',
                'description'   => '',
            ],
            'tab' => 'payment',
        ],
    ],
];

return $payment_fields;
