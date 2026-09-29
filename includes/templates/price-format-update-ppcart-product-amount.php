<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;
$ppcart_post_types = ppcart_query_post_types('product');
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration query.
$result = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type IN (" . implode(',', array_fill(0, count($ppcart_post_types), '%s')) . ')',
        $ppcart_post_types
    ),
    ARRAY_A
);
$productIds = array_column($result, 'ID');

if (!empty($productIds)) {
    foreach ($productIds as $product_id) {
        $update_pay_options = false;
        $payOptions = ppcart_get_post_meta($product_id, 'pay_options', true);
        if (!empty($payOptions)) {
            foreach ($payOptions as $key => $option) {
                if (isset($option['price']) && $option['price']) {
                    $amount = $this->check_price_format($option['price']);
                    if ($amount) {
                        $payOptions[$key]['price'] = $amount;
                        $update_pay_options = true;
                    }
                }

                if (isset($option['sale_price']) && $option['sale_price']) {
                    $amount = $this->check_price_format($option['sale_price']);
                    if ($amount) {
                        $payOptions[$key]['sale_price'] = $amount;
                        $update_pay_options = true;
                    }
                }

                if (isset($option['sign_up_fee']) && $option['sign_up_fee']) {
                    $amount = $this->check_price_format($option['sign_up_fee']);
                    if ($amount) {
                        $payOptions[$key]['sign_up_fee'] = $amount;
                        $update_pay_options = true;
                    }
                }

                if (isset($option['sale_sign_up_fee']) && $option['sale_sign_up_fee']) {
                    $amount = $this->check_price_format($option['sale_sign_up_fee']);
                    if ($amount) {
                        $payOptions[$key]['sale_sign_up_fee'] = $amount;
                        $update_pay_options = true;
                    }
                }
            }

            if ($update_pay_options) {
                ppcart_update_post_meta($product_id, 'pay_options', $payOptions);
            }
        }

        $update_custom_fields = false;
        $customFields = ppcart_get_post_meta($product_id, 'custom_fields', true);
        if (!empty($customFields)) {
            foreach ($customFields as $ckey => $custom_field) {
                if (isset($custom_field['qty_price'])) {
                    $amount = $this->check_price_format($custom_field['qty_price']);
                    if ($amount) {
                        $customFields[$key]['qty_price'] = $amount;
                        $update_custom_fields = true;
                    }
                }
            }

            if ($update_custom_fields) {
                ppcart_update_post_meta($product_id, 'custom_fields', $customFields);
            }
        }


        $update_coupons = false;
        $coupons = ppcart_get_post_meta($product_id, 'coupons', true);
        if (!empty($coupons)) {
            foreach ($coupons as $ckey => $coupon) {
                if (isset($coupon['amount'])) {
                    $amount = $this->check_price_format($coupon['amount']);
                    if ($amount) {
                        $coupons[$key]['amount'] = $amount;
                        $update_coupons = true;
                    }
                }

                if (isset($coupon['amount_recurring'])) {
                    $amount = $this->check_price_format($coupon['amount_recurring']);
                    if ($amount) {
                        $coupons[$key]['amount_recurring'] = $amount;
                        $update_coupons = true;
                    }
                }
            }
            if ($update_coupons) {
                ppcart_update_post_meta($product_id, 'coupons', $coupons);
            }
        }

        $obPrice = ppcart_get_post_meta($product_id, 'ob_price', true);
        if ($obPrice) {
            $amount = $this->check_price_format($obPrice);
            if ($amount) {
                $coupons[$key]['amount'] = $amount;
                ppcart_update_post_meta($product_id, 'ob_price', $amount);
            }
        }

        $update_bump_options = false;
        $bumpOptions = ppcart_get_post_meta($product_id, 'order_bump_options', true);
        if (!empty($bumpOptions)) {
            foreach ($bumpOptions as $bkey => $boption) {
                if (isset($boption['ob_price'])) {
                    $amount = $this->check_price_format($boption['ob_price']);
                    if ($amount) {
                        $bumpOptions[$bkey]['ob_price'] = $amount;
                        $update_bump_options = true;
                    }
                }
            }

            if ($update_bump_options) {
                ppcart_update_post_meta($product_id, 'order_bump_options', $bumpOptions);
            }
        }
    }
}
