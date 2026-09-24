<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Product metabox field definitions for payment plans.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Product_Metaboxes_Pay_Plan_Fields_Trait
{
    /**
     * Payment-plan repeater field schema for the product metabox.
     *
     * @param bool $save Unused; kept for the metabox save/render call signature.
     * @return array
     */
    private function pay_plan_fields($save = false)
    {

        $installmentsArr = ['-1' => __('Never expires', 'publishpress-cart')];
        for ($i = 2; $i <= 36; $i++) {
            $installmentsArr[$i] =  $i . __(' payments', 'publishpress-cart');
        }

        $fields = require __DIR__ . '/fields/pay-plan-fields.php';
        $filtered = apply_filters('ppcart_pay_plan_recurring_fields', $fields);
        if (is_array($filtered)) {
            $fields = $filtered;
        }

        $fields[] =
            [
                'checkbox' => [
                    'class'     => '',
                    'description'   => __("Hide from checkout forms. At least one visible payment plan is required.", 'publishpress-cart'),
                    'id'            => 'is_hidden',
                    'label'     => __('Hidden', 'publishpress-cart'),
                    'placeholder'   => '',
                    'type'      => 'checkbox',
                    'value'     => '',
                    'class_size' => '',
                    'conditional_logic' => '',
                ],
            ];
        return apply_filters('ppcart_pay_plan_fields', $fields);
    }
}
