<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Intent_Trait
{
    public function create_payment_intent()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-intent-create-payment-intent.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
