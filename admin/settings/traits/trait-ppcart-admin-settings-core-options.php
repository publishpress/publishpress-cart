<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Core settings option list (currency, decimals, and related defaults).
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Settings_Core_Options_Trait
{
    /**
     * Returns an array of options names, fields types, and default values
     *
     * @return      array           An array of options
     */

    public function get_options_list()
    {
        $to_email   = get_option('admin_email');
        $currencies = ppcart_get_currencies();
        if (! is_array($currencies)) {
            $currencies = [];
        }
        foreach ($currencies as $code => $name) {
            $symbols = ppcart_get_currency_symbols();
            $currencies[$code] = $name . ' (' . $symbols[$code] . ')';
        }
        $options = require __DIR__ . '/options/core-settings.php';

        $payment_options = $this->get_payment_fields();
        $integration_options = $this->get_integration_fields();
        $tax_options = $this->get_tax_fields();
        $invoice_option = $this->get_invoice_fields();
        $maintenance_options = $this->get_maintenance_fields();

        $ppcart_tab = apply_filters('ppcart_setting_tabs', []);

        $ppcart_tab_option = $this->get_tab_fields($ppcart_tab);

        $options = array_merge(
            is_array($options) ? $options : [],
            is_array($payment_options) ? $payment_options : [],
            is_array($integration_options) ? $integration_options : [],
            is_array($tax_options) ? $tax_options : [],
            is_array($invoice_option) ? $invoice_option : [],
            is_array($maintenance_options) ? $maintenance_options : [],
            is_array($ppcart_tab_option) ? $ppcart_tab_option : []
        );
        $filtered_options = apply_filters('_ppcart_option_list', $options);
        if (is_array($filtered_options)) {
            $options = $filtered_options;
        }

        if (function_exists('ppcart_prepare_email_template_options')) {
            $prepared_options = ppcart_prepare_email_template_options($options);
            if (is_array($prepared_options)) {
                $options = $prepared_options;
            }
        }

        return $options;
    }
}
