<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $stripe) {
    $stripe_mode = self::guess_mode_from_event($event);
    $stripe = self::get_client_for_mode($stripe_mode);
}

$charge_id = self::get($charge, 'id', '');
if ($charge_id) {
    try {
        $charge = $stripe->charges->retrieve(
            $charge_id,
            [
                'expand' => [ 'refunds' ],
            ]
        );
    } catch (Exception $e) {
        // Continue with event charge if retrieval fails.
    }
}

if (! $force && ! self::charge_belongs_to_site($charge)) {
    self::record_webhook_log(
        $event,
        'skipped',
        'Refund charge does not belong to this site.',
        [
            'charge_id'              => self::get($charge, 'id', ''),
            'invoice'                => self::get($charge, 'invoice', ''),
            'metadata_ppcart_product_id' => self::metadata($charge, 'ppcart_product_id', ''),
            'origin'                 => self::path($charge, [ 'metadata', 'origin' ], ''),
        ]
    );
    return false;
}

$cart_order = self::find_order_for_charge($charge);
if (! $cart_order || ! $cart_order->id) {
    self::record_webhook_log(
        $event,
        'not_matched',
        'Refund charge could not be matched to a local order.',
        [
            'charge_id'      => self::get($charge, 'id', ''),
            'payment_intent' => self::get($charge, 'payment_intent', ''),
        ]
    );
    return false;
}

return PPCart_Stripe_Sync_Context::run(
    function () use ($cart_order, $charge, $event) {
        $amount = absint(self::get($charge, 'amount', 0));
        $amount_refunded = absint(self::get($charge, 'amount_refunded', 0));
        $currency = strtoupper((string) self::get($charge, 'currency', $cart_order->currency));
        $refund_total = ppcart_format_stripe_number($amount_refunded, $currency);
        $refund_log = self::build_refund_log_from_charge($charge, $currency);

        ppcart_update_post_meta($cart_order->id, 'refund_log', $refund_log);
        ppcart_update_post_meta($cart_order->id, 'refund_amount', $refund_total);

        $cart_order->transaction_id = self::get($charge, 'id', $cart_order->transaction_id);
        $cart_order->refund_log = $refund_log;

        if ($amount_refunded > 0 && $amount_refunded >= $amount) {
            $cart_order->payment_status = 'refunded';
            $cart_order->status = 'refunded';
        } elseif ('refunded' === $cart_order->status) {
            $cart_order->payment_status = 'paid';
            $cart_order->status = 'paid';
        }

        $cart_order->store();
        self::mark_resource_event($cart_order->id, $event);
        self::record_webhook_log(
            $event,
            'applied',
            'Refund synced to local order.',
            [
                'order_id'        => $cart_order->id,
                'charge_id'       => self::get($charge, 'id', ''),
                'amount'          => $amount,
                'amount_display'  => ppcart_format_stripe_number($amount, $currency),
                'amount_refunded' => $amount_refunded,
                'amount_refunded_display' => $refund_total,
                'currency'        => $currency,
                'order_status'    => $cart_order->status,
            ]
        );

        return $cart_order;
    }
);
