<?php

if (! defined('ABSPATH')) {
    exit;
}


$invoice_status = self::get($invoice, 'status', '');

if ('invoice.payment_failed' === $event_type) {
    if ('succeeded' === $payment_intent_status) {
        return [ 'post_status' => 'paid', 'payment_status' => 'succeeded' ];
    }

    if (in_array($payment_intent_status, [ 'requires_action', 'processing', 'requires_confirmation' ], true)) {
        return [ 'post_status' => 'pending-payment', 'payment_status' => 'pending' ];
    }

    return [ 'post_status' => 'failed', 'payment_status' => 'failed' ];
}

if ('invoice.marked_uncollectible' === $event_type || 'uncollectible' === $invoice_status) {
    return [ 'post_status' => 'failed', 'payment_status' => 'uncollectible' ];
}

if ('invoice.payment_succeeded' === $event_type || 'paid' === $invoice_status) {
    return [ 'post_status' => 'paid', 'payment_status' => 'succeeded' ];
}

return false;
