<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Paypal_Settings_Trait
{
    public function sandbox_enabled()
    {

        $enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
        if ($enableSandbox != 'disable') {
            return true;
        }
        return false;
    }

    /**
     * Whether PayPal TLS verification is controlled by wp-config.php.
         *
         * @return bool
         */
    public static function is_ssl_verification_controlled_by_constant()
    {
        return defined(self::SSL_VERIFY_CONSTANT);
    }

    /**
     * Whether PayPal API requests should verify the peer certificate and host.
         *
         * @return bool
         */
    public static function ssl_verification_enabled()
    {
        return true;
    }

    public function maybe_add_paypal_pay_method($payment_methods, $post_id)
    {
        // Paypal
        if ($this->paypal_configured()) {
            $icon = '<img src="' . esc_url(PPCART_BASE_URL . 'public/images/cc/paypal-logo.png') . '" alt="" aria-hidden="true" loading="lazy" decoding="async" width="16" height="16" style="width:16px;height:16px;object-fit:contain;margin:0 6px -3px 0;">';

            if (apply_filters('ppcart_product_paypal_enabled', true, (int) $post_id)) {
                $payment_methods['paypal'] = [
                    'value' => 'paypal',
                    'label' => $icon . ' ' . esc_html__('PayPal', 'publishpress-cart'),
                    'single_label' => $icon . ' ' . esc_html__('Pay with PayPal', 'publishpress-cart'),
                ];
            }
        }
        return $payment_methods;
    }

    public function paypal_configured()
    {
        $enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
        if ($enableSandbox && get_option('_ppcart_paypal_enable')) {
            $paypalEmail = ($enableSandbox != 'disable') ? get_option('_ppcart_paypal_sandbox_email') : get_option('_ppcart_paypal_email');
            if ($paypalEmail) {
                return true;
            }
        }
        return false;
    }

    public function paypal_payment_method($payment_method, $order)
    {
        if ($order->pay_method != 'paypal') {
            return $payment_method;
        }
        return 'paypal';
    }

    public function paypal_sub_item_id($item_id, $item, $order)
    {
        if ($order->pay_method != 'paypal') {
            return $item_id;
        }

        return $item['id'] ?? $order->product_id;
    }

    public function maybe_add_paypal_enabled($payment_methods)
    {
        if ($this->paypal_configured()) {
            $payment_methods['paypal'] = esc_html__('PayPal', 'publishpress-cart');
        }
        return $payment_methods;
    }
}
