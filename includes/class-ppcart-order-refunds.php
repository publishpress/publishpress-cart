<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Helpers for reading order refund totals.
 */
class PPCart_Order_Refunds
{
    /**
     * Get the total refunded amount recorded for an order.
     *
     * @param int        $order_id   Order post ID.
     * @param array|null $refund_log Optional refund log override.
     * @return float
     */
    public static function get_refund_total($order_id, $refund_log = null)
    {
        if (null === $refund_log) {
            $refund_log = ppcart_get_post_meta($order_id, 'refund_log', true);
        }

        $log_total    = self::sum_refund_log($refund_log);
        $stored_total = self::parse_amount(ppcart_get_post_meta($order_id, 'refund_amount', true));

        return (float) max(0, max($log_total, $stored_total));
    }

    /**
     * Get the order amount after refunds.
     *
     * @param int        $order_id   Order post ID.
     * @param mixed|null $amount     Optional gross order amount override.
     * @param array|null $refund_log Optional refund log override.
     * @return float
     */
    public static function get_net_amount($order_id, $amount = null, $refund_log = null)
    {
        if (null === $amount) {
            $amount = ppcart_get_post_meta($order_id, 'amount', true);
        }

        $gross_amount = self::parse_amount($amount);
        $refund_total = min(self::get_refund_total($order_id, $refund_log), $gross_amount);

        return (float) max(0, $gross_amount - $refund_total);
    }

    /**
     * Determine whether the order has been partially refunded.
     *
     * @param int        $order_id   Order post ID.
     * @param mixed|null $amount     Optional gross order amount override.
     * @param array|null $refund_log Optional refund log override.
     * @return bool
     */
    public static function is_partially_refunded($order_id, $amount = null, $refund_log = null)
    {
        if (null === $amount) {
            $amount = ppcart_get_post_meta($order_id, 'amount', true);
        }

        $gross_amount = self::parse_amount($amount);
        if ($gross_amount <= 0) {
            return false;
        }

        $refund_total = self::get_refund_total($order_id, $refund_log);

        return $refund_total > 0 && $refund_total < $gross_amount;
    }

    /**
     * Get the IDs of partially refunded orders.
     *
     * Uses the same rule as is_partially_refunded(), so the result matches the
     * "Partially refunded" label on the Orders list. SQL only narrows the set to
     * orders that hold refund data. The amounts are compared in PHP, because
     * they are stored as formatted strings that SQL can't compare reliably.
     *
     * @param string|string[] $post_types Order post types.
     * @return int[]
     */
    public static function get_partially_refunded_order_ids($post_types)
    {
        global $wpdb;

        $post_types = array_values(array_filter(array_map('sanitize_key', (array) $post_types)));
        // Match the statuses the "All" view lists, so trashed orders stay out.
        $statuses = array_values(get_post_stati([ 'show_in_admin_all_list' => true ]));
        if (! $post_types || ! $statuses) {
            return [];
        }

        $meta_keys = [ ppcart_meta_key('refund_amount'), ppcart_meta_key('refund_log') ];
        $type_in   = implode(',', array_fill(0, count($post_types), '%s'));
        $status_in = implode(',', array_fill(0, count($statuses), '%s'));
        $key_in    = implode(',', array_fill(0, count($meta_keys), '%s'));

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholder lists are generated from the counts of the prepared values.
        $ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin list view; the result changes whenever a refund is stored.
            $wpdb->prepare(
                "SELECT DISTINCT pm.post_id
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE p.post_type IN ($type_in)
                AND p.post_status IN ($status_in)
                AND pm.meta_key IN ($key_in)
                AND pm.meta_value NOT IN ('', '0', 'a:0:{}')",
                array_merge($post_types, $statuses, $meta_keys)
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

        $ids = array_values(array_filter(array_map('absint', (array) $ids)));
        if (! $ids) {
            return [];
        }

        update_meta_cache('post', $ids);

        return array_values(array_filter($ids, static function ($order_id) {
            return self::is_partially_refunded($order_id);
        }));
    }

    /**
     * Sum refund log entries.
     *
     * @param mixed $refund_log Raw refund log.
     * @return float
     */
    public static function sum_refund_log($refund_log)
    {
        if (! is_array($refund_log)) {
            return 0;
        }

        if (class_exists('PPCart_Stripe_Sync')) {
            $refund_log = PPCart_Stripe_Sync::normalize_refund_log($refund_log);
        }

        $refund_total = 0;
        foreach ($refund_log as $refund_entry) {
            if (! is_array($refund_entry) || ! isset($refund_entry['amount'])) {
                continue;
            }

            $refund_total += abs(self::parse_amount($refund_entry['amount']));
        }

        return (float) max(0, $refund_total);
    }

    /**
     * Parse stored money values into decimals.
     *
     * @param mixed $amount Stored amount.
     * @return float
     */
    public static function parse_amount($amount)
    {
        if (is_int($amount) || is_float($amount)) {
            return (float) $amount;
        }

        if (! is_scalar($amount)) {
            return 0;
        }

        $amount = trim((string) $amount);
        if ('' === $amount) {
            return 0;
        }

        $amount = str_replace(',', '', $amount);
        $amount = preg_replace('/[^0-9.\-]/', '', $amount);

        if ('' === $amount || '-' === $amount || '.' === $amount || '-.' === $amount) {
            return 0;
        }

        return (float) $amount;
    }
}
