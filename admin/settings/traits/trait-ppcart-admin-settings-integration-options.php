<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Integration tab fields (Mailchimp, ActiveCampaign, webhooks, and related services).
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Settings_Integration_Options_Trait
{
    public function get_tab_fields($ppcart_tab)
    {

        if (!$ppcart_tab) {
            return $ppcart_tab;
        }

        $keys = array_keys($ppcart_tab);

        $key = $keys[0];

        $invoice_fields = [ $key . '-setting' => [
        ] ];

        $d = apply_filters('_ppcart_custom_option_list', $invoice_fields);
        return $d;
    }

    /**
     * Adding Maintenance Fields to the settings.
     *
     * @return array
     */
    public function get_maintenance_fields()
    {
        return [
            'maintenance-secrets' => [
                'maintenance-secret-storage' => [
                    'type'     => 'maintenance_secrets',
                    'label'    => '',
                    'settings' => [
                        'id'              => 'ppcart_maintenance_secret_storage',
                        'skip_register'   => true,
                    ],
                    'tab'      => 'maintenance',
                ],
            ],
            'maintenance-db-schema' => [
                'maintenance-db-schema-check' => [
                    'type'     => 'maintenance_db_schema',
                    'label'    => '',
                    'settings' => [
                        'id'            => 'ppcart_maintenance_db_schema',
                        'skip_register' => true,
                    ],
                    'tab'      => 'maintenance',
                ],
            ],
        ];
    }

    /**
     * Adding Integration Fields to the settings
     *
     * @return  array                       Array of the Integration fields with settings
     *
     */

    public function get_integration_fields()
    {
        $integration_fields = ['mailchimp' => [
                'mailchimp-api' => [
                    'type'          => 'password',
                    'label'         => esc_html__('Mailchimp API Key', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_mailchimp_api',
                        'value'         => '',
                        'description'   => '',
                    ],
                    'tab' => 'integrations',
                ],
            ],
            'activecampaign' => [
                'activecampaign-url' => [
                    'type'          => 'text',
                    'label'         => esc_html__('ActiveCampaign URL', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_activecampaign_url',
                        'value'         => '',
                        'description'   => '',
                    ],
                    'tab' => 'integrations',
                ],
                'activecampaign-sk' => [
                    'type'          => 'password',
                    'label'         => esc_html__('ActiveCampaign Secret Key', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_activecampaign_secret_key',
                        'value'         => '',
                        'description'   => '',
                    ],
                    'tab' => 'integrations',
                ],
            ],
            'membervault' => [
                'membervault-url' => [
                    'type'          => 'text',
                    'label'         => esc_html__('MemberVault URL', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_membervault_name',
                        'value'         => '',
                        'description'   => 'e.g. https://mysubdomain.vipmembervault.com',
                    ],
                    'tab' => 'integrations',
                ],
                'member-vault-api-key' => [
                    'type'          => 'password',
                    'label'         => esc_html__('MemberVault API Key', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_member_vault_api_key',
                        'value'         => '',
                        'description'   => '',
                    ],
                    'tab' => 'integrations',
                ],
            ],
            'sendfox' => [
                'sendfox-api-key' => [
                    'type'          => 'password',
                    'label'         => esc_html__('SendFox API Key', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_sendfox_api_key',
                        'value'         => '',
                        'description'   => '',
                    ],
                    'tab' => 'integrations',
                ],
            ],
            // Plugin-based integration: uses MailPoet's local API — no credentials.
            'mailpoet' => [
                'mailpoet-status' => [
                    'type'          => 'html',
                    'label'         => esc_html__('Status', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_mailpoet_status',
                        'value'         => '',
                        'skip_register' => true,
                        'description'   => class_exists('\\MailPoet\\API\\API')
                            ? '<p>' . esc_html__('MailPoet is active. Assign lists on each product’s Integrations settings — no API key is required.', 'publishpress-cart') . '</p>'
                            : '<p>' . esc_html__('Install and activate the MailPoet plugin to sync customers to lists. No API key is required.', 'publishpress-cart') . '</p>',
                    ],
                    'tab' => 'integrations',
                ],
            ],
            'fbads' => [
                'pay_info_event' => [
                    'type'          => 'checkbox',
                    'label'         => esc_html__('Add Payment Info', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_fb_add_payment_info',
                        'value'         => '',
                        'description'   => esc_html__('Sends an AddPaymentInfo event to an existing Meta Pixel installation.', 'publishpress-cart'),
                        'note'          => esc_html__('Requires Meta Pixel or another plugin/theme to load fbq on the checkout page.', 'publishpress-cart'),
                    ],
                    'tab' => 'integrations',
                ],
                'purchase_event' => [
                    'type'          => 'checkbox',
                    'label'         => esc_html__('Purchase Complete', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_fb_purchase',
                        'value'         => '',
                        'description'   => esc_html__('Sends a Purchase event to an existing Meta Pixel installation.', 'publishpress-cart'),
                        'note'          => esc_html__('Requires Meta Pixel or another plugin/theme to load fbq on the checkout page.', 'publishpress-cart'),
                    ],
                    'tab' => 'integrations',
                ],
                'lead_event' => [
                    'type'          => 'checkbox',
                    'label'         => esc_html__('Lead Captured', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_fb_lead',
                        'value'         => '',
                        'description'   => esc_html__('Sends a Lead event when a two-step checkout captures customer details.', 'publishpress-cart'),
                        'note'          => esc_html__('Requires Meta Pixel or another plugin/theme to load fbq on the checkout page.', 'publishpress-cart'),
                    ],
                    'tab' => 'integrations',
                ],
            ],
            'ga' => [
                'purchase_event' => [
                    'type'          => 'checkbox',
                    'label'         => esc_html__('Purchase Complete', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_ga_purchase',
                        'value'         => '',
                        'description'   => esc_html__('Sends a purchase event to an existing Google Analytics or Data Layer installation.', 'publishpress-cart'),
                        'note'          => esc_html__('Requires Google Analytics, Google Tag Manager, or another plugin/theme to load ga, gtag, or dataLayer on the checkout page.', 'publishpress-cart'),
                    ],
                    'tab' => 'integrations',
                ],
                'lead_event' => [
                    'type'          => 'checkbox',
                    'label'         => esc_html__('Lead Captured', 'publishpress-cart'),
                    'settings'      => [
                        'id'            => '_ppcart_ga_lead',
                        'value'         => '',
                        'description'   => esc_html__('Sends a generate_lead event when a two-step checkout captures customer details.', 'publishpress-cart'),
                        'note'          => esc_html__('Requires Google Analytics, Google Tag Manager, or another plugin/theme to load ga, gtag, or dataLayer on the checkout page.', 'publishpress-cart'),
                    ],
                    'tab' => 'integrations',
                ],
                'ga_type' => [
                    'type'          => 'select',
                    'label'         => esc_html__('Type', 'publishpress-cart'),
                    'settings'      => [
                        'note'      => '',
                        'id'            => '_ppcart_ga_type',
                        'value'         => 'test',
                        'description'   => esc_html__('Choose the event format that matches the analytics script already installed on this site.', 'publishpress-cart'),
                        'selections'    => [
                            '' => __('analytics.js', 'publishpress-cart'),
                            'ga4' => __('Google Analytics 4', 'publishpress-cart'),
                            'universal' => __('Universal Analytics', 'publishpress-cart'),
                            'datalayer_ga4' => __('DataLayer with GA4', 'publishpress-cart'),
                            'datalayer_enhanced' => __('DataLayer with Enhanced Ecommerce (UA)', 'publishpress-cart'),
                        ],
                    ],
                    'tab' => 'integrations',
                ],
            ],
        ];

        return apply_filters('_ppcart_integrations_option_list', $integration_fields);
    }
}
