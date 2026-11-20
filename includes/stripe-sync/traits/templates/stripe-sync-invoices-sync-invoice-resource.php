<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! self::invoice_belongs_to_site($invoice)) {
    return false;
}

$currency = strtoupper((string) self::get($invoice, 'currency', $currency));

$line = self::find_invoice_product_line($invoice);
if (! $line) {
    return false;
}

$sub_id = self::get($invoice, 'subscription', '');
if (! $sub_id) {
    $sub_id = self::get($line, 'subscription', '');
}
if (! $sub_id) {
    $sub_id = self::path($line, [ 'parent', 'subscription_item_details', 'subscription' ], '');
}

$subscription_local_id = self::metadata(self::path($invoice, [ 'subscription_details' ]), 'ppcart_subscription_id', '');
if (! $subscription_local_id) {
    $subscription_local_id = self::metadata($line, 'ppcart_subscription_id', '');
}

if (! $sub) {
    $sub = $sub_id ? PPCart_Subscription::get_by_sub_id($sub_id) : false;
    if (! $sub && $subscription_local_id) {
        $sub = new PPCart_Subscription(absint($subscription_local_id));
    }
}

if (! $sub || ! $sub->id) {
    return false;
}

$next_bill = self::path($line, [ 'period', 'end' ], '');
// A subscription that is set to cancel has no next payment. The invoice period
// end is the day it ends, not a billing date.
$is_pending_cancellation = ! empty($sub->cancel_date) && ! empty($sub->cancel_at);
if ($next_bill && ! $is_pending_cancellation && ! in_array($sub->status, [ 'canceled', 'paused', 'completed' ], true)) {
    PPCart_Stripe_Sync_Context::run(
        function () use ($sub, $next_bill, $event) {
            $sub->sub_next_bill_date = $next_bill;
            $sub->store();
            self::mark_resource_event($sub->id, $event);
        }
    );
}

$order_status = self::map_invoice_order_status($invoice, $event_type, $payment_intent_status);
if (! $order_status) {
    return false;
}

$payment_ids = self::invoice_payment_ids($invoice);
$charge_id = $payment_ids['charge'];
$payment_intent = $payment_ids['payment_intent'];
$existing = $charge_id ? PPCart_Order::get_by_trans_id($charge_id) : false;
if (! $existing && $payment_intent) {
    $existing = PPCart_Order::get_by_trans_id($payment_intent);
    if ($existing && $charge_id) {
        PPCart_Stripe_Sync_Context::run(
            function () use ($existing, $charge_id) {
                $existing->transaction_id = $charge_id;
                $existing->store();
            }
        );
    }
}

// Date renewal orders by when Stripe took the payment, as Stripe shows it.
$invoice_date = self::path($invoice, [ 'status_transitions', 'paid_at' ], 0);
if (! $invoice_date) {
    $invoice_date = self::path($invoice, [ 'status_transitions', 'finalized_at' ], self::get($invoice, 'created', time()));
}

// Stripe keeps a refunded invoice as "paid", so read the refund from the charge.
$refunded_charge = null;
if ($charge_id && 'paid' === $order_status['post_status'] && 'invoice.payment_failed' !== $event_type) {
    try {
        $charge_client = $stripe ? $stripe : self::get_client_for_mode(self::get($invoice, 'livemode', false) ? 'live' : 'test');
        $charge = $charge_client->charges->retrieve($charge_id, [ 'expand' => [ 'refunds' ] ]);
        if (absint(self::get($charge, 'amount_refunded', 0)) > 0) {
            $refunded_charge = $charge;
        }
    } catch (Throwable $e) {
        // Without the charge, keep the invoice status.
    }
}

// An order marked refunded whose charge is only partly refunded goes back to
// paid with its refund recorded. The charge sync does that. The invoice can't,
// because Stripe keeps a refunded invoice as "paid".
if (
    $existing
    && $refunded_charge
    && ('refunded' === $existing->status || 'refunded' === $existing->payment_status)
    && absint(self::get($refunded_charge, 'amount_refunded', 0)) < absint(self::get($refunded_charge, 'amount', 0))
) {
    return self::sync_charge_refund($refunded_charge, $charge_client, $event, true);
}

return PPCart_Stripe_Sync_Context::run(
    function () use ($existing, $sub, $invoice, $charge_id, $payment_intent, $payment_intent_status, $order_status, $invoice_date, $event, $event_type, $currency, $refunded_charge) {
        $order = $existing;

        if (! $order) {
            $order = $sub->first_order();
            if (
                $order
                && (
                    'refunded' === $order->status
                    || 'refunded' === $order->payment_status
                    || (((! $order->transaction_id || strpos($order->transaction_id, 'ch_') !== 0) && $sub->order_count() == 1) === false)
                )
            ) {
                // A refunded first order is never reused for a new payment.
                $order = $sub->new_order();
            }
        }

        if (! $order) {
            return false;
        }

        if ('invoice.payment_failed' === $event_type) {
            $current_order = new PPCart_Order($order->id);
            if ($current_order->id) {
                $order = $current_order;
            }

            if (
                'succeeded' === $payment_intent_status
                || in_array($order->status, [ 'paid', 'completed', 'refunded' ], true)
            ) {
                self::mark_resource_event($order->id, $event);
                return $order;
            }
        }

        // Stripe keeps a refunded invoice as "paid", so the invoice never
        // proves a refund was undone. Leave refunded orders to the charge and
        // refund sync, or they flip back to paid and re-run integrations.
        if ($existing && ('refunded' === $order->status || 'refunded' === $order->payment_status)) {
            self::mark_resource_event($order->id, $event);
            return $order;
        }

        $values = [
            'transaction_id' => $charge_id ? $charge_id : $payment_intent,
            'amount'         => ppcart_format_stripe_number(self::get($invoice, 'amount_paid', 0), $currency),
            'payment_status' => $order_status['payment_status'],
            'status'         => $order_status['post_status'],
        ];
        $trigger_integrations = true;
        if ($refunded_charge) {
            $order->refund_log = self::build_refund_log_from_charge($refunded_charge, $currency);

            if (absint(self::get($refunded_charge, 'amount_refunded', 0)) >= absint(self::get($refunded_charge, 'amount', 0))) {
                $values['payment_status'] = 'refunded';
                $values['status'] = 'refunded';

                // An imported payment that was refunded long ago must not
                // grant or remove access now. A paid order that turns
                // refunded still runs its refund integrations.
                $trigger_integrations = (bool) $existing;
            }
        }

        if (! self::order_matches_values($order, $values)) {
            $order->transaction_id = $values['transaction_id'];
            $order->amount = $values['amount'];
            $order->payment_status = $values['payment_status'];
            $order->status = $values['status'];
            $order->store($trigger_integrations);
        }
        if ($refunded_charge && $order->id) {
            ppcart_update_post_meta($order->id, 'refund_log', $order->refund_log);
            ppcart_update_post_meta($order->id, 'refund_amount', ppcart_format_stripe_number(absint(self::get($refunded_charge, 'amount_refunded', 0)), $currency));
        }
        $order->set_date_from_timestamp($invoice_date);

        self::mark_resource_event($order->id, $event);
        do_action('ppcart_stripe_invoice_response', $order->get_data());

        return $order;
    }
);
