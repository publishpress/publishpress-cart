<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Order_Custom_Fields_Trait
{
    public static function get_custom_fields_from_post($ppcart_product)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-custom-fields-get-custom-fields-from-post.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function get_custom_fields_post_data($pid)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-custom-fields-get-custom-fields-post-data.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
