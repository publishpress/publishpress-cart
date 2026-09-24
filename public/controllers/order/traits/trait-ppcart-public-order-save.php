<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Order_Save_Trait
{
    public function save_order_to_db($order_info = false, $subscription = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-save-save-order-to-db.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
