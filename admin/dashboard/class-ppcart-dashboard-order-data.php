<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Monthly order totals and status counts for the dashboard widget.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Dashboard_Order_Data
{
    public function get_summary()
    {
        $total_orders = $this->get_total_orders();
        $total_sales  = $this->get_total_sales();

        return [
            'total_orders'        => $total_orders,
            'total_sales'         => $total_sales,
            'average_order_value' => $total_orders > 0 ? $total_sales / $total_orders : 0,
            'pending_orders'      => $this->get_orders('pending-payment'),
            'paid_orders'         => $this->get_orders('paid'),
            'refunded_orders'     => $this->get_orders('refunded'),
        ];
    }

    private function get_total_orders()
    {
        global $wpdb;

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_post_types() returns a placeholder list prepared from canonical post types.
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type IN (" . ppcart_sql_in_post_types('order') . ") AND post_date >= %s",
            wp_date('Y-m-01')
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $query is built via $wpdb->prepare(); IN() lists come from ppcart_sql_in_post_types().
        return (int) $wpdb->get_var($query);
    }

    private function get_total_sales()
    {
        global $wpdb;

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_post_types() returns a placeholder list prepared from canonical post types.
        $query = $wpdb->prepare(
            "SELECT SUM(meta_value) FROM $wpdb->postmeta WHERE meta_key = %s AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type IN (" . ppcart_sql_in_post_types('order') . ") AND post_date >= %s)",
            ppcart_meta_key('amount'),
            wp_date('Y-m-01')
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $query is built via $wpdb->prepare() above.
        return (float) $wpdb->get_var($query);
    }

    private function get_orders($status)
    {
        global $wpdb;

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_post_types() returns a placeholder list prepared from canonical post types.
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->postmeta
            WHERE meta_key = %s
            AND meta_value = %s
            AND post_id IN (
                SELECT ID FROM $wpdb->posts
                WHERE post_type IN (" . ppcart_sql_in_post_types('order') . ")
                AND post_date >= %s
                AND ID NOT IN (
                    SELECT post_id FROM $wpdb->postmeta
                    WHERE meta_key = %s
                )
            )",
            ppcart_meta_key('status'),
            $status,
            wp_date('Y-m-01'),
            ppcart_meta_key('renewal')
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $query is built via $wpdb->prepare() above.
        return (int) $wpdb->get_var($query);
    }
}
