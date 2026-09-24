<?php

if (! defined('ABSPATH')) {
    exit;
}


$default_product_setting_tabs = [
    'general'        => __('General', 'publishpress-cart'),
    'pricing'        => __('Payment Plans', 'publishpress-cart'),
    'access'         => __('Purchase Restrictions', 'publishpress-cart'),
    'fields'         => __('Form Fields & Settings', 'publishpress-cart'),
    'confirmation'   => __('Confirmation', 'publishpress-cart'),
    'notifications'  => __('Notifications', 'publishpress-cart'),
    'integrations'   => __('Integrations', 'publishpress-cart'),
];

$product_setting_tabs = apply_filters('ppcart_product_setting_tabs', $default_product_setting_tabs);
if (! is_array($product_setting_tabs) || [] === $product_setting_tabs) {
    $product_setting_tabs = $default_product_setting_tabs;
}

if (function_exists('ppcart_pro_locked_product_tabs')) {
    $pro_tabs = ppcart_pro_locked_product_tabs();

    if (! empty($pro_tabs)) {
        // Which Pro tab(s) follow which free tab.
        $insert_after = [
            'access' => [ 'ppcart_pro_payment_methods' ],
            'fields' => [ 'ppcart_pro_coupons', 'ppcart_pro_order_bumps', 'ppcart_pro_upsell_path' ],
            'files'  => [ 'ppcart_pro_shipping', 'ppcart_pro_affiliates' ],
        ];

        $merged = [];
        foreach ($product_setting_tabs as $tab_id => $tab_label) {
            $merged[ $tab_id ] = $tab_label;

            if (! empty($insert_after[ $tab_id ])) {
                foreach ($insert_after[ $tab_id ] as $pro_id) {
                    if (isset($pro_tabs[ $pro_id ])) {
                        $merged[ $pro_id ] = $pro_tabs[ $pro_id ];
                    }
                }
            }
        }

        // Append any Pro tab whose anchor tab was not present.
        foreach ($pro_tabs as $pro_id => $pro_label) {
            if (! isset($merged[ $pro_id ])) {
                $merged[ $pro_id ] = $pro_label;
            }
        }

        $product_setting_tabs = $merged;
    }
}

return $product_setting_tabs;
