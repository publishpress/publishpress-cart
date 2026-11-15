<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metaboxes_Integration_Fields_Trait
{
    private function set_integration_field_group($save, $product_id = 0)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-set-integration-field-group.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
