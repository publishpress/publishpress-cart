<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Tax, invoice, and payment field definitions for the settings screen.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Settings_Billing_Options_Trait
{
    /**
     * Adding Tax Fields to the settings
     *
     * @return  array                       Array of the tax fields with settings
     *
     */
    public function get_tax_fields()
    {
        $tax_fields = require __DIR__ . '/options/tax-fields.php';
        return $tax_fields;
    }

    public function get_invoice_fields()
    {
        $invoice_fields = require __DIR__ . '/options/invoice-fields.php';
        return apply_filters('_ppcart_invoice_option_list', $invoice_fields);
    }

    /**
     * Adding Payment Fields to the settings
     *
     * @return  array                       Array of the Payment fields with settings
     *
     */

    public function get_payment_fields()
    {
        $paypal_ssl_verify_option   = class_exists('PPCart_Paypal') ? PPCart_Paypal::SSL_VERIFY_OPTION : '_ppcart_paypal_ssl_verify';
        $paypal_ssl_verify_locked   = true;
        $paypal_ssl_verify_value    = 1;
        $paypal_ssl_verify_note     = __('Always enabled for PayPal API requests.', 'publishpress-cart');

        $payment_fields = require __DIR__ . '/options/payment-fields.php';
        return apply_filters('_ppcart_payment_field_option_list', $payment_fields);
    }
}
