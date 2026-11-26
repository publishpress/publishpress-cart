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

// An invoice paid with no charge, for example a $0 invoice covered by a
// coupon or the customer's credit balance, has no payment ID. Find its order
// by the Stripe invoice ID that the sync stores on each order.
$invoice_id = (string) self::get($invoice, 'id', '');
if (! $existing && ! $charge_id && ! $payment_intent && $invoice_id) {
    $invoice_order_ids = get_posts(
        [
            'post_type'      => ppcart_query_post_types('order'),
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One order by its Stripe invoice ID, during a manual or webhook sync.
            'meta_query'     => [
                [
                    'key'   => ppcart_meta_key('stripe_invoice_id'),
                    'value' => $invoice_id,
                ],
            ],
        ]
    );
    if ($invoice_order_ids) {
        $existing = new PPCart_Order((int) $invoice_order_ids[0]);
    }
}

// Orders migrated from old StudioCart versions, and orders that an earlier
// sync created for an invoice with no charge, have no payment ID. Match the
// invoice to such an order of this subscription by amount and payment date,
// so Sync with Stripe doesn't create a second order for the same payment.
// An invoice with no payment ID is only matched when it was paid, so an
// unpaid invoice can't turn a paid $0 order into a failed one.
if (! $existing && ($charge_id || $payment_intent || 'paid' === $order_status['post_status'])) {
    $legacy_order_id = self::find_unlinked_order_for_invoice($sub, self::get($invoice, 'amount_paid', 0), $currency, $invoice_date, $invoice_id);
    if ($legacy_order_id) {
        $existing = new PPCart_Order($legacy_order_id);
        if ($charge_id || $payment_intent) {
            PPCart_Stripe_Sync_Context::run(
                function () use ($legacy_order_id, $charge_id, $payment_intent) {
                    ppcart_update_post_meta($legacy_order_id, 'transaction_id', $charge_id ? $charge_id : $payment_intent);
                }
            );
            $existing->transaction_id = $charge_id ? $charge_id : $payment_intent;
        }
    }
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

$synced_order = PPCart_Stripe_Sync_Context::run(
    function () use ($existing, $sub, $invoice, $invoice_id, $charge_id, $payment_intent, $payment_intent_status, $order_status, $invoice_date, $event, $event_type, $currency, $refunded_charge) {
        $order = $existing;

        if (! $order) {
            $order = $sub->first_order();
            $first_order_invoice_id = ($order && ! empty($order->id)) ? (string) ppcart_get_post_meta($order->id, 'stripe_invoice_id', true) : '';
            if (! $order || empty($order->id)) {
                // A subscription with no order yet, for example one imported
                // from Stripe, gets a new order built from its own details.
                $order = $sub->new_order();
            } elseif (
                $order
                && (
                    'refunded' === $order->status
                    || 'refunded' === $order->payment_status
                    // An unpaid invoice never takes over a first order that is already paid.
                    || ('paid' !== $order_status['post_status'] && in_array($order->status, [ 'paid', 'completed' ], true))
                    // A first order already synced from another invoice is a different payment.
                    || ($invoice_id && '' !== $first_order_invoice_id && $invoice_id !== $first_order_invoice_id)
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
            // A refunded order can miss its refund record, for example one
            // migrated from StudioCart. Add the Stripe refunds that its log
            // doesn't have yet. Only meta is written, so no integrations run.
            if ($refunded_charge && $order->id) {
                $current_log = ppcart_get_post_meta($order->id, 'refund_log', true);
                $current_log = self::normalize_refund_log(is_array($current_log) ? $current_log : []);
                $refund_log = self::refund_log_for_charge($order->id, $refunded_charge, $currency);
                if (false !== $refund_log) {
                    if ($refund_log !== $current_log) {
                        ppcart_update_post_meta($order->id, 'refund_log', $refund_log);
                        $order->refund_log = $refund_log;
                    }
                    $refund_amount = ppcart_format_stripe_number(self::log_total_minor($refund_log, $currency), $currency);
                    if ((string) $refund_amount !== (string) ppcart_get_post_meta($order->id, 'refund_amount', true)) {
                        ppcart_update_post_meta($order->id, 'refund_amount', $refund_amount);
                    }
                }
            }
            self::mark_resource_event($order->id, $event);
            return $order;
        }

        $values = [
            'transaction_id' => $charge_id ? $charge_id : $payment_intent,
            'amount'         => ppcart_format_stripe_number(self::get($invoice, 'amount_paid', 0), $currency),
            'payment_status' => $order_status['payment_status'],
            'status'         => $order_status['post_status'],
        ];
        // A completed order is already paid. An order not yet stored as a
        // Stripe order, for example one migrated as Cash, keeps its status on
        // its first sync unless Stripe fully refunded it, and runs no
        // integrations.
        $first_stripe_sync = $existing && 'stripe' !== $existing->pay_method;
        if ($existing && ($first_stripe_sync || ('completed' === $order->status && 'paid' === $values['status']))) {
            $values['payment_status'] = $order->payment_status;
            $values['status'] = $order->status;
        }
        $trigger_integrations = ! $first_stripe_sync;
        $refund_log = $refunded_charge && $order->id ? self::refund_log_for_charge($order->id, $refunded_charge, $currency) : null;
        if ($refunded_charge && false === $refund_log) {
            // Manual refunds that don't match Stripe need a person to check.
            self::mark_resource_event($order->id, $event);
            return $order;
        }
        if ($refunded_charge) {
            $order->refund_log = null !== $refund_log ? $refund_log : self::build_refund_log_from_charge($refunded_charge, $currency);

            if (absint(self::get($refunded_charge, 'amount_refunded', 0)) >= absint(self::get($refunded_charge, 'amount', 0))) {
                $values['payment_status'] = 'refunded';
                $values['status'] = 'refunded';

                // An imported payment that was refunded long ago must not
                // grant or remove access now. A paid order that turns
                // refunded still runs its refund integrations.
                $trigger_integrations = $existing && ! $first_stripe_sync;
            }
        }

        if (! self::order_matches_values($order, $values)) {
            $order->transaction_id = $values['transaction_id'];
            $order->amount = $values['amount'];
            $order->payment_status = $values['payment_status'];
            $order->status = $values['status'];
            $order->store($trigger_integrations);
        }
        // An order found by its charge can be missing its subscription, for
        // example a renewal created before the subscription had a first order.
        // Link it and fill in the customer from the subscription. Only meta is
        // written, so no integrations run again.
        if ($existing && $order->id && empty($order->subscription_id)) {
            ppcart_update_post_meta($order->id, 'subscription_id', $sub->id);
            $order->subscription_id = $sub->id;
            foreach (
                [
                    'firstname', 'lastname', 'first_name', 'last_name', 'customer_name', 'customer_id',
                    'email', 'phone', 'user_account', 'product_id', 'product_name', 'currency',
                ] as $field
            ) {
                if (empty($order->$field) && isset($sub->$field) && '' !== $sub->$field && null !== $sub->$field) {
                    ppcart_update_post_meta($order->id, $field, $sub->$field);
                    $order->$field = $sub->$field;
                }
            }
        }
        if ($refunded_charge && $order->id) {
            $log_minor = self::log_total_minor((array) $order->refund_log, $currency);
            ppcart_update_post_meta($order->id, 'refund_log', $order->refund_log);
            ppcart_update_post_meta($order->id, 'refund_amount', ppcart_format_stripe_number($log_minor ? $log_minor : absint(self::get($refunded_charge, 'amount_refunded', 0)), $currency));
        }
        $order->set_date_from_timestamp($invoice_date);
        if ($invoice_id && $order->id && $invoice_id !== (string) ppcart_get_post_meta($order->id, 'stripe_invoice_id', true)) {
            ppcart_update_post_meta($order->id, 'stripe_invoice_id', $invoice_id);
        }

        self::mark_resource_event($order->id, $event);
        do_action('ppcart_stripe_invoice_response', $order->get_data());

        return $order;
    }
);

// Orders migrated from StudioCart can show a Stripe payment as Cash. An order
// paid by this invoice's charge is a Stripe order in the invoice's mode.
if ($synced_order && ($charge_id || $payment_intent)) {
    PPCart_Stripe_Sync_Context::run(
        function () use ($synced_order, $invoice) {
            self::mark_order_as_stripe_payment($synced_order, $invoice);
        }
    );
}

return $synced_order;
