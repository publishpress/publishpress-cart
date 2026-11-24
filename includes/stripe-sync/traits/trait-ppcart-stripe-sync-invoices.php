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
     * @param object|null           $stripe                Stripe client, used to read refunds on the invoice charge.
     * @return PPCart_Order|false
     */
    public static function sync_invoice_resource($invoice, $sub = null, $event_type = '', $event = null, $currency = 'USD', $payment_intent_status = '', $stripe = null)
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

    /**
     * Find the charge and PaymentIntent ids that paid an invoice.
     *
     * Newer Stripe API versions removed invoice.charge and invoice.payment_intent;
     * the ids are under invoice.payments instead.
     *
     * @param object|array $invoice Stripe invoice resource.
     * @return array{charge: string, payment_intent: string}
     */
    private static function invoice_payment_ids($invoice)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-invoice-payment-ids.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Find a paid order of a subscription that has no charge ID and matches
     * an invoice's amount and payment date.
     *
     * @param PPCart_Subscription $sub      Local subscription.
     * @param int                 $amount   Invoice amount paid, in Stripe's smallest unit.
     * @param string              $currency Invoice currency.
     * @param int                 $paid_at  Invoice payment time (Unix).
     * @param string              $invoice_id Stripe invoice ID. Orders linked to another invoice are skipped.
     * @return int Order ID, or 0.
     */
    private static function find_unlinked_order_for_invoice($sub, $amount, $currency, $paid_at, $invoice_id = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-invoices-find-unlinked-order-for-invoice.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
