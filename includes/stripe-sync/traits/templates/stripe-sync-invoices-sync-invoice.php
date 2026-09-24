<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $stripe) {
    $stripe_mode = self::guess_mode_from_event($event);
    $stripe = self::get_client_for_mode($stripe_mode);
}

$payment_intent_status = '';

// A failed invoice event can be emitted while 3DS authentication is still in
// progress. For this event only, inspect the latest PaymentIntent state while
// retaining the webhook invoice used by the existing order-matching flow.
if ('invoice.payment_failed' === $event_type) {
    $payment_intent = self::get($invoice, 'payment_intent', '');

    if (! $payment_intent) {
        $invoice_payments = self::path($invoice, [ 'payments', 'data' ], []);
        if (is_array($invoice_payments)) {
            foreach ($invoice_payments as $invoice_payment) {
                $candidate = self::path($invoice_payment, [ 'payment', 'payment_intent' ], '');
                if ($candidate) {
                    $payment_intent = $candidate;
                    break;
                }
            }
        }
    }

    $payment_intent_id = is_string($payment_intent)
        ? $payment_intent
        : self::get($payment_intent, 'id', '');
    $payment_intent_status = strtolower((string) self::get($payment_intent, 'status', ''));

    if ($payment_intent_id) {
        try {
            $latest_payment_intent = $stripe->paymentIntents->retrieve($payment_intent_id);
            $payment_intent_status = strtolower((string) self::get($latest_payment_intent, 'status', $payment_intent_status));
        } catch (Exception $e) {
            // Preserve the previous failure behavior when Stripe is unavailable.
        }
    }
}

$invoice_id = self::get($invoice, 'id', '');
if ($invoice_id && 'invoice.payment_failed' !== $event_type) {
    try {
        $invoice = $stripe->invoices->retrieve($invoice_id);
    } catch (Exception $e) {
        // Continue with event invoice if retrieval fails.
    }
}

return self::sync_invoice_resource($invoice, null, $event_type, $event, 'USD', $payment_intent_status);
