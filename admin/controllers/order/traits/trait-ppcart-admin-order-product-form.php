<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Order_Product_Form_Trait
{
    public function product_form_callback($post)
    {
        include __DIR__ . '/../templates/product-form.php';
    }
}
