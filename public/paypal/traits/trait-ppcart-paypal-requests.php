<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Paypal_Requests_Trait
{
    public function paypal_oauthtoken()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-paypal-oauthtoken.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    protected function build_paypal_url($order, $sub)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-build-paypal-url.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function paypal_request()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-paypal-request.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function paypal_process_payment()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-paypal-process-payment.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function run_pdt_check($order_info, $paypal_data)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-run-pdt-check.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Send a POST request to the PayPal API.
         * @param string $paypalUrl PayPal API URL.
         * @param array $args Post data.
         */

    public function doCurlRequest($paypalUrl, $args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-docurlrequest.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
