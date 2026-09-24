<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Checkout_Upsell_Trait
{
    public function process_upsell()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/checkout-upsell-ppcart-process-upsell.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
