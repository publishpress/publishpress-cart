<?php

if (! defined('ABSPATH')) {
    exit;
}


try {
    $stripe = self::get_client_for_mode($mode);
    if (0 === strpos($payment_id, 'pi_')) {
        $intent = $stripe->paymentIntents->retrieve($payment_id, [ 'expand' => [ 'latest_charge' ] ]);
        $charge = self::get($intent, 'latest_charge', null);
        if (is_string($charge) && '' !== $charge) {
            $charge = $stripe->charges->retrieve($charge, [ 'expand' => [ 'refunds' ] ]);
        } elseif ($charge) {
            $charge = $stripe->charges->retrieve((string) self::get($charge, 'id', ''), [ 'expand' => [ 'refunds' ] ]);
        }
    } else {
        $charge = $stripe->charges->retrieve($payment_id, [ 'expand' => [ 'refunds' ] ]);
    }
} catch (Throwable $e) {
    return new WP_Error('ppcart_import_charge_stripe', $e->getMessage());
}

if (! $charge || 'succeeded' !== (string) self::get($charge, 'status', '')) {
    return new WP_Error('ppcart_import_charge_status', __('Only a successful payment can be imported.', 'publishpress-cart'));
}

if (true !== self::get($charge, 'captured', false)) {
    return new WP_Error('ppcart_import_charge_uncaptured', __('This payment isn\'t captured yet. Capture it in Stripe, then import it.', 'publishpress-cart'));
}

$intent_id = self::get($charge, 'payment_intent', '');
$intent_id = is_string($intent_id) ? $intent_id : (string) self::get($intent_id, 'id', '');

// A payment that paid an invoice belongs to that invoice's subscription.
// Sync with Stripe imports it, so it can't be added here by hand.
// Newer Stripe API versions don't return charge.invoice. Ask Stripe which
// invoice the PaymentIntent paid instead.
$invoice_error = new WP_Error('ppcart_import_charge_invoice', __('This payment paid a Stripe invoice. Use Sync with Stripe on its subscription instead.', 'publishpress-cart'));
$charge_invoice = self::get($charge, 'invoice', '');
$charge_invoice = is_string($charge_invoice) ? $charge_invoice : (string) self::get($charge_invoice, 'id', '');
if ('' !== $charge_invoice) {
    return $invoice_error;
}

if ('' !== $intent_id) {
    try {
        $invoice_payments = $stripe->invoicePayments->all(
            [
                'payment' => [ 'type' => 'payment_intent', 'payment_intent' => $intent_id ],
                'limit'   => 1,
            ]
        );
    } catch (Throwable $e) {
        return new WP_Error(
            'ppcart_import_charge_invoice_lookup',
            /* translators: %s: error message from Stripe */
            sprintf(__('Cart couldn\'t check whether this payment paid a Stripe invoice, so it wasn\'t imported. Try again. Stripe said: %s', 'publishpress-cart'), $e->getMessage())
        );
    }

    $invoice_payments = self::get($invoice_payments, 'data', []);
    if (! empty($invoice_payments)) {
        return $invoice_error;
    }
}

$exists = self::find_imported_charge_order([ (string) self::get($charge, 'id', ''), $intent_id ]);
if ($exists) {
    return $exists;
}

return $charge;
