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
if ($next_bill && ! in_array($sub->status, [ 'canceled', 'paused', 'completed' ], true)) {
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

$charge_id = self::get($invoice, 'charge', '');
$payment_intent = self::get($invoice, 'payment_intent', '');
$payment_intent = is_string($payment_intent) ? $payment_intent : self::get($payment_intent, 'id', '');
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

$invoice_date = self::path($invoice, [ 'status_transitions', 'finalized_at' ], self::get($invoice, 'created', time()));

return PPCart_Stripe_Sync_Context::run(
    function () use ($existing, $sub, $invoice, $charge_id, $payment_intent, $payment_intent_status, $order_status, $invoice_date, $event, $event_type, $currency) {
        $order = $existing;

        if (! $order) {
            $order = $sub->first_order();
            if ($order && (((! $order->transaction_id || strpos($order->transaction_id, 'ch_') !== 0) && $sub->order_count() == 1) === false)) {
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

        $values = [
            'transaction_id' => $charge_id ? $charge_id : $payment_intent,
            'amount'         => ppcart_format_stripe_number(self::get($invoice, 'amount_paid', 0), $currency),
            'payment_status' => $order_status['payment_status'],
            'status'         => $order_status['post_status'],
        ];
        if (! self::order_matches_values($order, $values)) {
            $order->transaction_id = $values['transaction_id'];
            $order->amount = $values['amount'];
            $order->payment_status = $values['payment_status'];
            $order->status = $values['status'];
            $order->store();
        }
        $order->set_date_from_timestamp($invoice_date);

        self::mark_resource_event($order->id, $event);
        do_action('ppcart_stripe_invoice_response', $order->get_data());

        return $order;
    }
);
