<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Page_Customer_Trait
{
    public function customer_has_subscription($atts)
    {
        $atts = shortcode_atts([
            'email' => '',
            'user_id' => '',
            'product_id' => '',
            'plan_id' => false,
        ], $atts);

        $atts['has_subscription'] = true;

        return $this->customer_bought_product($atts);
    }

    public function customer_bought_product($atts)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/page-customer-customer-bought-product.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
