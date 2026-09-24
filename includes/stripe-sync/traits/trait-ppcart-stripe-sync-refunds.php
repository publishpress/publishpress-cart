<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Refunds_Trait
{
    /**
     * Sync paid/refunded state for a Stripe charge.
     *
     * @param object|array $charge Stripe charge resource.
     * @param object|null  $event  Source webhook event.
     * @param object|null  $stripe Stripe client.
     * @return PPCart_Order|false
     */
    public static function sync_charge_status($charge, $event = null, $stripe = null)
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
