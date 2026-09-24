<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metabox_Plan_Options_Trait
{
    public function get_service_type()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-plan-options-get-ppcart-service-type.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_trigger_option()
    {
        $options = [
            'purchased'  => __('Product Purchased', 'publishpress-cart'),
            'refunded'   => __('Product Refunded', 'publishpress-cart'),
            'pending'    => __('COD Order Created', 'publishpress-cart'),
            'active'     => __('Subscription Active', 'publishpress-cart'),
            'completed'  => __('Installment Plan Completed', 'publishpress-cart'),
            'canceled'   => __('Subscription Canceled', 'publishpress-cart'),
            'paused'     => __('Subscription Paused', 'publishpress-cart'),
            'renewal' => __('Subscription Renewal Charged', 'publishpress-cart'),
            'failed' => __('Subscription Renewal Failed', 'publishpress-cart'),
        ];

        return apply_filters('ppcart_integration_trigger_options', $options);
    }

    public static function get_plans($key)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
        if (!isset($_GET['post'])) {
            return;
        }
        $options = [];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value from current admin post.
        $post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

        if ($product_id = ppcart_get_post_meta($post_id, $key, true)) {
            if (is_array($product_id)) {
                foreach ($product_id as $prod_id) {
                    if (ppcart_is_meta_field_id($key, 'order_bump_options') || $key === 'order_bump_options') {
                        $pid = $prod_id['ob_product'];
                    }
                    $options[$pid] = self::get_plan_data($pid);
                }
            } else {
                $options = self::get_plan_data($product_id);
            }
        }
        return $options;
    }

    public static function get_plan_data($product_id)
    {
        $product_plan_data = ppcart_get_post_meta($product_id, 'pay_options', true);

        if (!$product_plan_data) {
            return ["" => esc_html__('No plans found', 'publishpress-cart')];
        } else {
            $options = [];
            foreach ($product_plan_data as $val) {
                $name = $val['option_name'] ?? $val['option_id'];
                $options[$val['option_id']] = $name;
            }
            if (!empty($options)) {
                return $options;
            } else {
                return ["" => esc_html__('No plans found', 'publishpress-cart')];
            }
        }
    }

    public static function get_payment_plans($plansOnly = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-plan-options-get-payment-plans.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_bumps()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-plan-options-get-bumps.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_fields($save)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-plan-options-get-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function product_options()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-plan-options-product-options.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function upsell_paths()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-plan-options-upsell-paths.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
