<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Subscription_Form_Trait
{
    private function main_product_sub_row($order, $sub = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-form-main-product-sub-row.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function subscription_form_callback($post)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/admin-subscription-form-callback.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
