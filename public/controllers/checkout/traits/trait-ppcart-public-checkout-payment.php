<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Checkout_Payment_Trait
{
    public function process_payment()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/checkout-payment-ppcart-process-payment.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
