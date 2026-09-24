<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Invoices_Trait
{
    /**
     * Refetch and sync a Stripe invoice from a webhook event.
     *
     * @param object|array $invoice    Stripe invoice resource.
     * @param object|null  $stripe     Stripe client.
     * @param string       $event_type Source event type.
     * @param object|null  $event      Source webhook event.
     * @return PPCart_Order|false
     */
    public static function sync_invoice($invoice, $stripe = null, $event_type = '', $event = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-sync-invoice.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Project a Stripe invoice onto the local renewal order.
     *
     * @param object|array          $invoice    Stripe invoice resource.
     * @param PPCart_Subscription|null $sub        Local subscription, if already known.
     * @param string                $event_type Source event type.
     * @param object|null           $event      Source webhook event.
     * @param string                $currency              Currency fallback.
     * @param string                $payment_intent_status Latest PaymentIntent status for a failed invoice event.
     * @return PPCart_Order|false
     */
    public static function sync_invoice_resource($invoice, $sub = null, $event_type = '', $event = null, $currency = 'USD', $payment_intent_status = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-sync-invoice-resource.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Map Stripe invoice state to local order payment state.
     *
     * @param object|array $invoice    Stripe invoice resource.
     * @param string       $event_type            Source event type.
     * @param string       $payment_intent_status Latest PaymentIntent status for a failed invoice event.
     * @return array|false
     */
    private static function map_invoice_order_status($invoice, $event_type = '', $payment_intent_status = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-map-invoice-order-status.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Check whether a local order already has the projected Stripe values.
     *
     * @param PPCart_Order $order  Local order.
     * @param array     $values Field/value projection.
     * @return bool
     */
    private static function order_matches_values($order, $values)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-order-matches-values.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Check whether an invoice belongs to this site.
     *
     * @param object|array $invoice Stripe invoice resource.
     * @return bool
     */
    private static function invoice_belongs_to_site($invoice)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-invoice-belongs-to-site.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Find the invoice line that represents the PublishPress Cart subscription product.
     *
     * @param object|array $invoice Stripe invoice resource.
     * @return object|array|false
     */
    private static function find_invoice_product_line($invoice)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-find-invoice-product-line.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
