<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Update_Intent_Trait
{
    public function update_payment_intent_amt($pid = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-update-intent-amount.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
