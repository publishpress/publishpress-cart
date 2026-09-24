<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Price_Format
{
    public $thousand_sep = '';
    public $decimal_sep  = '';

    public function __construct()
    {
        $this->thousand_sep = get_option('_ppcart_thousand_separator');
        $this->decimal_sep = get_option('_ppcart_decimal_separator');
        $this->api_format_price();
    }

    public function api_format_price()
    {

        if ($this->thousand_sep != '' || $this->decimal_sep != '') {
            update_option('ppcart_price_formatted', time());

            $this->update_ppcart_product_amount();
            $this->update_ppcart_us_path_amount();
            $this->update_ppcart_order_amount();
            $this->update_order_items_amount();
            $this->update_order_itemmeta_amount();
            $this->update_ppcart_subscriptions_amount();

            update_option('ppcart_price_formatted', 'yes');
        }
    }

    public function check_price_format($price)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/price-format-check-price-format.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update_ppcart_product_amount()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/price-format-update-ppcart-product-amount.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update_ppcart_us_path_amount()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/price-format-update-ppcart-us-path-amount.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update_ppcart_order_amount()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/price-format-update-ppcart-order-amount.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update_order_items_amount()
    {
        global $wpdb;
        $table_name = ppcart_live_table('order_items');
        $query = $wpdb->prepare('SELECT order_item_id,total_amount,tax_amount from %i', $table_name);
        $result = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- One-off migration query on plugin-owned table.
        if (!empty($result)) {
            foreach ($result as $item_amount) {
                $amount = $this->check_price_format($item_amount['total_amount']);
                if ($amount && $amount != $item_amount['total_amount']) {
                    $updated = $wpdb->update($table_name, ['total_amount' => $amount], ['order_item_id' => $item_amount['order_item_id']]); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration update on plugin-owned table.
                }

                $amount = $this->check_price_format($item_amount['tax_amount']);
                if ($amount && $amount != $item_amount['tax_amount']) {
                    $updated = $wpdb->update($table_name, ['tax_amount' => $amount], ['order_item_id' => $item_amount['order_item_id']]); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration update on plugin-owned table.
                }
            }
        }
    }

    public function update_order_itemmeta_amount()
    {
        global $wpdb;
        $table_name = ppcart_live_table('order_itemmeta');
        $query = $wpdb->prepare(
            "SELECT meta_id,meta_value from %i WHERE meta_key IN ('unit_price','subtotal','discount_amount','shipping_amount','sign_up_fee')",
            $table_name
        ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One-off migration query scans legacy numeric meta values to normalize formatting.
        $result = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One-off migration query on plugin-owned item meta table.
        if (!empty($result)) {
            foreach ($result as $item_meta) {
                $amount = $this->check_price_format($item_meta['meta_value']);
                if ($amount && $amount != $item_meta['meta_value']) {
                    $updated = $wpdb->update($table_name, ['meta_value' => $amount], ['meta_id' => $item_meta['meta_id']]); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One-off migration update on plugin-owned item meta table.
                }
            }
        }
    }

    public function update_ppcart_subscriptions_amount()
    {
        global $wpdb;
        $query = "SELECT ID from $wpdb->posts WHERE post_type IN (" . ppcart_sql_in_post_types('subscription') . ")";
        $result = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- One-off migration query to normalize subscription amounts.
        $subscriptionIds = array_column($result, 'ID');

        foreach ($subscriptionIds as $key => $subs_id) {
            $subsMetakeys = ['sign_up_fee','tax_amount','sub_amount','sub_discount','main_offer','main_offer_amt'];
            foreach ($subsMetakeys as $meta_key) {
                $meta_value = ppcart_get_post_meta($subs_id, $meta_key, true);
                if ($meta_value) {
                    $amount = $this->check_price_format($meta_value);
                    if ($amount && $amount != $meta_value) {
                        ppcart_update_post_meta($subs_id, $meta_key, $amount);
                    }
                }
            }
        }
    }
}
