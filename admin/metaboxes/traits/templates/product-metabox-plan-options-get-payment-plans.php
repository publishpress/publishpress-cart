<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
if (!isset($_GET['post'])) {
    return;
}

$integrations = ['' => __('Any', 'publishpress-cart')];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value from current admin post.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

$items = ppcart_get_post_meta($current_post_id, 'pay_options', true);
if (is_array($items)) {
    foreach ($items as $item) {
        $product_type = $item['product_type'] ?? '';
        if ((isset($item['option_id']) && $item['option_id'] != null) && (isset($item['option_name']) && $item['option_name'] != null)) {
            if (!$plansOnly || ($plansOnly == 'coupons' && $product_type != 'free') || ($plansOnly && $product_type == 'recurring')) {
                $integrations[$item['option_id']] = $item['option_name'];
            }

            if (!$plansOnly && isset($item['sale_option_name'])) {
                $integrations[$item['option_id'] . '_sale'] = $item['sale_option_name'] . ' ' . __('(on sale)', 'publishpress-cart');
            }
        }
    }
}

return apply_filters('ppcart_integration_plan_targets', $integrations, $plansOnly);
