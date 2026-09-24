<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $stripe) {
    $stripe_mode = self::guess_mode_from_event($event);
    $stripe = self::get_client_for_mode($stripe_mode);
}

$charge_id = self::get($refund, 'charge', '');
$payment_intent_id = self::get($refund, 'payment_intent', '');
if (! $charge_id && $payment_intent_id) {
    try {
        $payment_intent = $stripe->paymentIntents->retrieve($payment_intent_id);
        $charge_id = self::get($payment_intent, 'latest_charge', '');
    } catch (Exception $e) {
        $charge_id = '';
    }
}

if (! $charge_id && $payment_intent_id) {
    $cart_order = PPCart_Order::get_by_trans_id($payment_intent_id);
    if ($cart_order && 0 === strpos((string) $cart_order->transaction_id, 'ch_')) {
        $charge_id = $cart_order->transaction_id;
    }
}

if (! $charge_id) {
    self::record_webhook_log(
        $event,
        'not_matched',
        'Refund event did not include a charge or resolvable payment intent.',
        [
            'refund_id'      => self::get($refund, 'id', ''),
            'payment_intent' => $payment_intent_id,
        ]
    );
    return false;
}

$charge = $stripe->charges->retrieve($charge_id);
return self::sync_charge_refund($charge, $stripe, $event, $force);
