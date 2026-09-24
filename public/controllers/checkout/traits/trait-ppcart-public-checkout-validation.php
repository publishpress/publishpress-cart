<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Checkout_Validation_Trait
{
    public function check_product_purchase_limit()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/checkout-validation-check-purchase-limit.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function validate_order_form_submission()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/checkout-validation-validate-order-form.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
