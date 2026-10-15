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
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration query on plugin-owned table.
        $result = $wpdb->get_results(
            $wpdb->prepare('SELECT order_item_id,total_amount,tax_amount from %i', $table_name),
            ARRAY_A
        );
        $total_changes = [];
        $tax_changes = [];
        foreach ((array) $result as $item_amount) {
            $amount = $this->check_price_format($item_amount['total_amount']);
            if ($amount && $amount != $item_amount['total_amount']) {
                $total_changes[(int) $item_amount['order_item_id']] = $amount;
            }

            $amount = $this->check_price_format($item_amount['tax_amount']);
            if ($amount && $amount != $item_amount['tax_amount']) {
                $tax_changes[(int) $item_amount['order_item_id']] = $amount;
            }

            if (count($total_changes) >= 200) {
                $this->update_amounts_in_batches($table_name, 'order_item_id', 'total_amount', $total_changes);
                $total_changes = [];
            }
            if (count($tax_changes) >= 200) {
                $this->update_amounts_in_batches($table_name, 'order_item_id', 'tax_amount', $tax_changes);
                $tax_changes = [];
            }
        }

        $this->update_amounts_in_batches($table_name, 'order_item_id', 'total_amount', $total_changes);
        $this->update_amounts_in_batches($table_name, 'order_item_id', 'tax_amount', $tax_changes);
    }

    public function update_order_itemmeta_amount()
    {
        global $wpdb;
        $table_name = ppcart_live_table('order_itemmeta');
        $meta_keys  = ['unit_price', 'subtotal', 'discount_amount', 'shipping_amount', 'sign_up_fee'];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration query on plugin-owned item meta table.
        $result = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT meta_id,meta_value from %i WHERE meta_key IN (' . implode(',', array_fill(0, count($meta_keys), '%s')) . ')',
                array_merge([ $table_name ], $meta_keys)
            ),
            ARRAY_A
        );
        $changes = [];
        foreach ((array) $result as $item_meta) {
            $amount = $this->check_price_format($item_meta['meta_value']);
            if ($amount && $amount != $item_meta['meta_value']) {
                $changes[(int) $item_meta['meta_id']] = $amount;
            }
            if (count($changes) >= 200) {
                $this->update_amounts_in_batches($table_name, 'meta_id', 'meta_value', $changes);
                $changes = [];
            }
        }

        $this->update_amounts_in_batches($table_name, 'meta_id', 'meta_value', $changes);
    }

    /**
     * Apply distinct normalized amounts with bounded, prepared CASE updates.
     *
     * @param string $table  Plugin-owned table name.
     * @param string $id_key Primary key column.
     * @param string $column Amount column.
     * @param array  $changes ID-to-amount map.
     */
    private function update_amounts_in_batches($table, $id_key, $column, $changes)
    {
        global $wpdb;

        foreach (array_chunk($changes, 200, true) as $batch) {
            $sql = 'UPDATE %i SET %i = CASE %i';
            $values = [$table, $column, $id_key];
            foreach ($batch as $id => $amount) {
                $sql .= ' WHEN %d THEN %s';
                $values[] = $id;
                $values[] = $amount;
            }

            $sql .= ' ELSE %i END WHERE %i IN (' . implode(',', array_fill(0, count($batch), '%d')) . ')';
            $values[] = $column;
            $values[] = $id_key;
            $values = array_merge($values, array_keys($batch));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- SQL contains only fixed fragments and generated placeholders; values and identifiers are prepared below.
            $wpdb->query($wpdb->prepare($sql, $values));
        }
    }

    public function update_ppcart_subscriptions_amount()
    {
        global $wpdb;
        $post_types = ppcart_query_post_types('subscription');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration query to normalize subscription amounts.
        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID from {$wpdb->posts} WHERE post_type IN (" . implode(',', array_fill(0, count($post_types), '%s')) . ')',
                $post_types
            ),
            ARRAY_A
        );
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
