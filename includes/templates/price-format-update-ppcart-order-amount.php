<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-off migration query uses ppcart_sql_in_post_types(), which returns a prepared canonical post type list.
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_post_types() returns a placeholder list prepared from canonical post types.
$result = $wpdb->get_results(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type IN (" . ppcart_sql_in_post_types('order') . ")",
    ARRAY_A
);
// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
$orderIds = array_column($result, 'ID');

foreach ($orderIds as $key => $order_id) {
    $orderMetakeys = ['invoice_total','invoice_subtotal','amount','main_offer_amt','pre_tax_amount','tax_amount','shipping_amount','shipping_tax'];

    foreach ($orderMetakeys as $meta_key) {
        $meta_value = ppcart_get_post_meta($order_id, $meta_key, true);
        if ($meta_value) {
            $amount = $this->check_price_format($meta_value);
            if ($amount) {
                ppcart_update_post_meta($order_id, $meta_key, $amount);
            }
        }
    }

    $custom_prices = ppcart_get_post_meta($order_id, 'custom_prices', true);
    if (!empty($custom_prices)) {
        foreach ($custom_prices as $name => $price) {
            if ($price) {
                $amount = $this->check_price_format($price);
                if ($amount) {
                    $custom_prices[$name] =  $amount;
                }
            }
        }
        ppcart_update_post_meta($order_id, 'custom_prices', $custom_prices);
    }

    $order_bumps = ppcart_get_post_meta($order_id, 'order_bumps', true);
    if (!empty($order_bumps)) {
        foreach ($order_bumps as $obKey => $obValue) {
            if (isset($obValue['amount'])) {
                $amount = $this->check_price_format($obValue['amount']);
                if ($amount) {
                    $order_bumps[$obKey]['amount'] =  $amount;
                }
            }
        }
        ppcart_update_post_meta($order_id, 'order_bumps', $order_bumps);
    }
}
