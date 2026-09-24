<?php

if (!defined('ABSPATH')) {
    exit;
}

$product_plan_data = ppcart_get_post_meta($product_id, 'pay_options', true);

if (!$product_plan_data) {
    return ["" => esc_html__('No plans found', 'publishpress-cart')];
} else {
    $options = [];
    foreach ($product_plan_data as $val) {
        $product_type = $val['product_type'] ?? '';
        if ($product_type == 'recurring') {
            $options[$val['option_id']] = $val['option_name'];
        }
    }
    if (!empty($options)) {
        return $options;
    } else {
        return ["" => esc_html__('No plans found', 'publishpress-cart')];
    }
}
