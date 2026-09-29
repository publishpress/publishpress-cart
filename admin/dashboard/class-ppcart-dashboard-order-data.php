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

        $order_types = ppcart_query_post_types('order');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dashboard widget count.
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN (" . implode(',', array_fill(0, count($order_types), '%s')) . ') AND post_date >= %s',
                array_merge($order_types, [ wp_date('Y-m-01') ])
            )
        );
    }

    private function get_total_sales()
    {
        global $wpdb;

        $order_types = ppcart_query_post_types('order');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dashboard widget total.
        return (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type IN (" . implode(',', array_fill(0, count($order_types), '%s')) . ') AND post_date >= %s)',
                array_merge([ ppcart_meta_key('amount') ], $order_types, [ wp_date('Y-m-01') ])
            )
        );
    }

    private function get_orders($status)
    {
        global $wpdb;

        $order_types = ppcart_query_post_types('order');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dashboard widget count.
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = %s
                AND meta_value = %s
                AND post_id IN (
                    SELECT ID FROM {$wpdb->posts}
                    WHERE post_type IN (" . implode(',', array_fill(0, count($order_types), '%s')) . ")
                    AND post_date >= %s
                    AND ID NOT IN (
                        SELECT post_id FROM {$wpdb->postmeta}
                        WHERE meta_key = %s
                    )
                )",
                array_merge(
                    [ ppcart_meta_key('status'), $status ],
                    $order_types,
                    [ wp_date('Y-m-01'), ppcart_meta_key('renewal') ]
                )
            )
        );
    }
}
