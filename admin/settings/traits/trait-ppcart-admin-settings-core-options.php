<?php

defined('ABSPATH') || exit;

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
     * @param bool $mcp_context Include native controls used by private MCP actions before applying configuration locks.
     * @return      array           An array of options
     */

    public function get_options_list($mcp_context = false)
    {
        $to_email   = ppcart_get_admin_email();
        $currencies = ppcart_get_currencies();
        if (! is_array($currencies)) {
            $currencies = [];
        }
        $symbols = ppcart_get_currency_symbols();
        foreach ($currencies as $code => $name) {
            $currencies[$code] = $name . ' (' . $symbols[$code] . ')';
        }
        $options = require __DIR__ . '/options/core-settings.php';

        $payment_options = $this->get_payment_fields($mcp_context);
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
        if ($mcp_context) {
            $options = apply_filters('ppcart_mcp_setting_definitions', $options);
        }
        $filtered_options = apply_filters('ppcart_option_list', $options);
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
