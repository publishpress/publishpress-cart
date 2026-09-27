<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Settings_Sections_Trait
{
    /**
     * Registers settings sections with WordPress
     */
    public function register_sections()
    {
        include __DIR__ . '/templates/settings-sections-register.php';
    }

    /**
     * Registers Integration Tab settings sections with WordPress
     */
    public function register_integration_tab_section()
    {
        include __DIR__ . '/templates/settings-sections-integrations.php';
    }

    /**
     * Registers Payment Gateway Tab settings sections with WordPress
     */
    public function register_payment_gateway_tab_section()
    {
        $payment_gateways = [
            'cashondelivery' => __('Cash on Delivery', 'publishpress-cart'),
            'stripe' => __('Stripe', 'publishpress-cart'),
            'paypal' => __('PayPal', 'publishpress-cart'),
        ];
        $payment_gateways = apply_filters('_ppcart_payment_gateway_tab_section', $payment_gateways);
        foreach ($payment_gateways as $payment_gateway_key => $payment_gateway) :
            add_settings_section(
                $this->plugin_name . '-' . $payment_gateway_key,
                apply_filters($this->plugin_name . 'section-title-' . $payment_gateway_key, esc_html($payment_gateway)),
                [ $this, 'section_settings' ],
                $this->plugin_name . '-payment'
            );
        endforeach;
        do_action('_ppcart_register_gateways', $this, $this->plugin_name . '-payment');
    }

    /**
     * Registers Email Tab settings sections with WordPress
     */
    public function register_email_tab_section()
    {
        include __DIR__ . '/templates/settings-sections-emails.php';
    }

    /**
     * Registers Tax Tab settings sections with WordPress
     */
    public function register_tax_tab_section()
    {
        $taxes = [
            'tax-setting' => __('Tax Options', 'publishpress-cart'),
        ];
        $taxes = apply_filters('_ppcart_taxes_tab_section', $taxes);
        foreach ($taxes as $tax_key => $tax) :
            add_settings_section(
                $this->plugin_name . '-' . $tax_key,
                apply_filters($this->plugin_name . 'section-title-' . $tax_key, esc_html($tax)),
                [ $this, 'section_settings' ],
                $this->plugin_name . '-tax'
            );
        endforeach;
    }

    /**
    * Registers Tax Tab settings sections with WordPress
    */
    public function register_invoice_tab_section()
    {
        $invoices = [
            'invoice-setting' => __('Invoice Options', 'publishpress-cart'),
        ];
        $invoices = apply_filters('_ppcart_invoice_tab_section', $invoices);

        foreach ($invoices as $invoice_key => $invoice) :
            add_settings_section(
                $this->plugin_name . '-' . $invoice_key,
                apply_filters($this->plugin_name . 'section-title-' . $invoice_key, esc_html($invoice)),
                [ $this, 'section_settings' ],
                $this->plugin_name . '-invoice'
            );
        endforeach;
    }

    /**
     * Registers Maintenance Tab settings sections with WordPress.
     *
     * @return void
     */
    public function register_maintenance_tab_section()
    {
        add_settings_section(
            $this->plugin_name . '-maintenance-secrets',
            apply_filters($this->plugin_name . 'section-title-maintenance-secrets', esc_html__('Security', 'publishpress-cart')),
            [ $this, 'section_settings' ],
            $this->plugin_name . '-maintenance'
        );
    }

    /**
     * Registers the Maintenance database schema section.
     *
     * @return void
     */
    public function register_maintenance_db_schema_section()
    {
        add_settings_section(
            $this->plugin_name . '-maintenance-db-schema',
            apply_filters($this->plugin_name . 'section-title-maintenance-db-schema', esc_html__('Database schema', 'publishpress-cart')),
            [ $this, 'section_settings' ],
            $this->plugin_name . '-maintenance'
        );
    }

    /**
    * Register Custom Tab settings section with Wordpress
    */

    public function register_tab_section($ppcart_tabs)
    {
        include __DIR__ . '/templates/settings-sections-custom-tabs.php';
    }
}
