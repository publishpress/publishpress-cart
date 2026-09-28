<?php

if (! defined('ABSPATH')) {
    exit;
}


$resource_id = static function ($value) {
    return is_string($value) ? $value : (string) self::get($value, 'id', '');
};

$charge_id = $resource_id(self::get($invoice, 'charge', ''));
$payment_intent = self::get($invoice, 'payment_intent', '');
$payment_intent_id = $resource_id($payment_intent);

if (! $charge_id && ! $payment_intent_id) {
    $invoice_payments = self::path($invoice, [ 'payments', 'data' ], []);
    if (is_array($invoice_payments)) {
        foreach ($invoice_payments as $invoice_payment) {
            if ('paid' !== self::get($invoice_payment, 'status', '')) {
                continue;
            }

            $charge_id = $resource_id(self::path($invoice_payment, [ 'payment', 'charge' ], ''));
            $payment_intent = self::path($invoice_payment, [ 'payment', 'payment_intent' ], '');
            $payment_intent_id = $resource_id($payment_intent);
            break;
        }
    }
}

if (! $charge_id && $payment_intent_id) {
    $latest_charge = is_string($payment_intent) ? '' : self::get($payment_intent, 'latest_charge', '');

    if (! $latest_charge) {
        try {
            $stripe = self::get_client_for_mode(self::get($invoice, 'livemode', false) ? 'live' : 'test');
            $latest_charge = self::get($stripe->paymentIntents->retrieve($payment_intent_id), 'latest_charge', '');
        } catch (Exception $e) {
            // Fall back to the PaymentIntent id when Stripe is unavailable.
        }
    }

    $charge_id = $resource_id($latest_charge);
}

return [
    'charge'         => $charge_id,
    'payment_intent' => $payment_intent_id,
];
