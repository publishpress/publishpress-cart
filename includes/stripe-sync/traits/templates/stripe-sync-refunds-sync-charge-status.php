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

if (! self::charge_belongs_to_site($charge)) {
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
        $values = [
            'transaction_id' => self::get($charge, 'id', $cart_order->transaction_id),
            'payment_status' => 'paid',
            'status'         => 'paid',
        ];
        if (! self::order_matches_values($cart_order, $values)) {
            $cart_order->transaction_id = $values['transaction_id'];
            $cart_order->payment_status = $values['payment_status'];
            $cart_order->status = $values['status'];
            $cart_order->store();
        }
        self::mark_resource_event($cart_order->id, $event);

        return $cart_order;
    }
);
