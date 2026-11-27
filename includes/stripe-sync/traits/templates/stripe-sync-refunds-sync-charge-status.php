<?php

if (! defined('ABSPATH')) {
    exit;
}


if ($stripe && self::get($charge, 'id', '')) {
    try {
        $charge = $stripe->charges->retrieve(self::get($charge, 'id', ''));
    } catch (Exception $e) {
        // Continue with event charge if retrieval fails.
    }
}

if (! $force && ! self::charge_belongs_to_site($charge)) {
    self::record_webhook_log(
        $event,
        'skipped',
        'Charge does not belong to this site.',
        [
            'charge_id'              => self::get($charge, 'id', ''),
            'invoice'                => self::get($charge, 'invoice', ''),
            'metadata_ppcart_product_id' => self::metadata($charge, 'ppcart_product_id', ''),
            'origin'                 => self::path($charge, [ 'metadata', 'origin' ], ''),
        ]
    );
    return false;
}

// A failed or pending charge never marks an order paid.
$charge_status = (string) self::get($charge, 'status', 'succeeded');
if ('succeeded' !== $charge_status || false === self::get($charge, 'paid', true)) {
    self::record_webhook_log(
        $event,
        'skipped',
        'Charge has not succeeded.',
        [
            'charge_id' => self::get($charge, 'id', ''),
            'status'    => $charge_status,
        ]
    );
    return false;
}

if (self::get($charge, 'refunded', false) || absint(self::get($charge, 'amount_refunded', 0)) > 0) {
    return self::sync_charge_refund($charge, $stripe, $event, true);
}

$cart_order = self::find_order_for_charge($charge);
if (! $cart_order || ! $cart_order->id) {
    self::record_webhook_log(
        $event,
        'not_matched',
        'Paid charge could not be matched to a local order.',
        [
            'charge_id'      => self::get($charge, 'id', ''),
            'payment_intent' => self::get($charge, 'payment_intent', ''),
        ]
    );
    return false;
}

return PPCart_Stripe_Sync_Context::run(
    function () use ($cart_order, $charge, $event) {
        // A completed order is already paid. An order migrated as Cash gets
        // Stripe's status, and runs no integrations when the merchant syncs
        // it. A refunded migrated order may have been refunded outside
        // Stripe, so it stays refunded.
        $migrated = self::is_migrated_stripe_order($cart_order);
        $values = [
            'transaction_id' => self::get($charge, 'id', $cart_order->transaction_id),
        ];
        $keep_refunded = $migrated && 'refunded' === $cart_order->status;
        if ($keep_refunded) {
            self::flag_refund_for_check(
                $cart_order->id,
                __('Stripe shows no refund for this payment; the order stays refunded. Check the refunds in Stripe.', 'publishpress-cart')
            );
        } elseif ('completed' !== $cart_order->status) {
            $values['payment_status'] = 'paid';
            $values['status'] = 'paid';
        }
        if (! self::order_matches_values($cart_order, $values)) {
            foreach ($values as $field => $value) {
                $cart_order->$field = $value;
            }
            $cart_order->store(! self::is_quiet_stripe_sync($cart_order));
        }
        self::mark_order_as_stripe_payment($cart_order, $charge);
        self::mark_resource_event($cart_order->id, $event);

        return $cart_order;
    }
);
