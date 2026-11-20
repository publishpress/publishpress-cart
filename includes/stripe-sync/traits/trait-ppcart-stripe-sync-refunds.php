<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Refunds_Trait
{
    /**
     * @param PPCart_Order $order  Persisted native order.
     * @param object|null  $stripe Stripe client. Defaults to the client for the order's mode.
     * @return string
     */
    public static function sync_order_by_local($order, $stripe = null)
    {
        return include __DIR__ . '/templates/stripe-sync-refunds-sync-order-by-local.php';
    }

    /**
     * Get the Stripe charge or payment intent ID that a manual order sync can load.
     *
     * @param object|null $order Order object or order data.
     * @return string The `ch_` or `pi_` ID, or an empty string when the order can't be synced.
     */
    public static function get_order_sync_payment_id($order)
    {
        if (! $order || ! isset($order->pay_method) || 'stripe' !== $order->pay_method) {
            return '';
        }

        foreach ([ 'stripe_charge_id', 'transaction_id' ] as $field) {
            $payment_id = isset($order->$field) && is_scalar($order->$field) ? (string) $order->$field : '';
            if (0 === strpos($payment_id, 'ch_') || 0 === strpos($payment_id, 'pi_')) {
                return $payment_id;
            }
        }

        return '';
    }

    /**
     * Sync paid/refunded state for a Stripe charge.
     *
     * @param object|array $charge Stripe charge resource.
     * @param object|null  $event  Source webhook event.
     * @param object|null  $stripe Stripe client.
     * @param bool         $force  Skip the site ownership check. Use it only when
     *                             the charge was loaded from a local order's own
     *                             payment ID.
     * @return PPCart_Order|false
     */
    public static function sync_charge_status($charge, $event = null, $stripe = null, $force = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-sync-charge-status.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Resolve a Stripe refund event to its charge, then sync the charge refund state.
     *
     * @param object|array $refund Stripe refund resource.
     * @param object|null  $stripe Stripe client.
     * @param object|null  $event  Source webhook event.
     * @param bool         $force  Whether to bypass site ownership checks.
     * @return PPCart_Order|false
     */
    public static function sync_refund($refund, $stripe = null, $event = null, $force = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-sync-refund.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Sync the cumulative Stripe refund state for a charge.
     *
     * Stripe charge amount_refunded is authoritative for totals; individual
     * refund objects only populate the local audit log.
     *
     * @param object|array $charge Stripe charge resource.
     * @param object|null  $stripe Stripe client.
     * @param object|null  $event  Source webhook event.
     * @param bool         $force  Whether to bypass site ownership checks.
     * @return PPCart_Order|false
     */
    public static function sync_charge_refund($charge, $stripe = null, $event = null, $force = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-sync-charge-refund.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Build a local refund log from Stripe charge refunds.
     *
     * @param object|array $charge   Stripe charge resource with refunds expanded.
     * @param string       $currency Stripe currency code.
     * @return array
     */
    public static function build_refund_log_from_charge($charge, $currency)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-build-refund-log-from-charge.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Normalize refund log entries by stable refund IDs.
     *
     * @param array $entries Existing local refund log entries.
     * @return array
     */
    public static function normalize_refund_log($entries)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-normalize-refund-log.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Find the local order linked to a Stripe charge.
     *
     * @param object|array $charge Stripe charge resource.
     * @return PPCart_Order|false
     */
    private static function find_order_for_charge($charge)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-find-order-for-charge.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Resolve the local order ID for a Stripe charge.
     *
     * @param object|array $charge Stripe charge resource.
     * @return int
     */
    private static function get_order_id_from_charge($charge)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-get-order-id-from-charge.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Check whether a charge belongs to this site.
     *
     * @param object|array $charge Stripe charge resource.
     * @return bool
     */
    private static function charge_belongs_to_site($charge)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-refunds-charge-belongs-to-site.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
