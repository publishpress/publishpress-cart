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
