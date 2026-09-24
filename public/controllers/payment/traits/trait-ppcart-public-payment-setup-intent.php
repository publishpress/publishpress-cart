<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Setup_Intent_Trait
{
    public function create_setup_intent()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-setup-intent-create-setup-intent.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
