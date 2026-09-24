<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metaboxes_Sales_Fields_Trait
{
    private function set_sales_field_groups($save, $post_id)
    {
        $this->access = $this->get_sales_access_fields();
        $this->payments = $this->get_sales_payment_fields($post_id);
        $this->pricing = $this->get_sales_pricing_fields($save);
        $this->fields = $this->get_sales_checkout_fields();
    }

    private function get_sales_access_fields()
    {
        return array_merge(
            $this->get_sales_stock_fields(),
            $this->get_sales_customer_limit_fields(),
            $this->get_sales_cart_window_fields()
        );
    }

    private function get_sales_stock_fields()
    {
        return include __DIR__ . '/../field-groups/sales-stock-fields.php';
    }

    private function get_sales_customer_limit_fields()
    {
        return include __DIR__ . '/../field-groups/sales-customer-limit-fields.php';
    }

    private function get_sales_cart_window_fields()
    {
        return include __DIR__ . '/../field-groups/sales-cart-window-fields.php';
    }

    private function get_sales_payment_fields($post_id)
    {
        $payments = [];
        return apply_filters('ppcart_product_payments_fields', $payments, $post_id);
    }

    private function get_sales_pricing_fields($save)
    {
        $pricing = array_merge(
            $this->get_sales_discount_fields(),
            $this->get_sales_plan_fields($save)
        );

        return apply_filters('ppcart_pricing_fields', $pricing, $save);
    }

    private function get_sales_discount_fields()
    {
        return include __DIR__ . '/../field-groups/sales-discount-fields.php';
    }

    private function get_sales_plan_fields($save)
    {
        return include __DIR__ . '/../field-groups/sales-plan-fields.php';
    }

    private function get_sales_checkout_fields()
    {
        return include __DIR__ . '/../field-groups/sales-checkout-fields.php';
    }
}
