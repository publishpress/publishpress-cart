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

// A payment that paid an invoice belongs to that invoice's subscription.
// Sync with Stripe imports it, so it can't be added here by hand.
$charge_invoice = self::get($charge, 'invoice', '');
$charge_invoice = is_string($charge_invoice) ? $charge_invoice : (string) self::get($charge_invoice, 'id', '');
if ('' !== $charge_invoice) {
    return new WP_Error('ppcart_import_charge_invoice', __('This payment paid a Stripe invoice. Use Sync with Stripe on its subscription instead.', 'publishpress-cart'));
}

$intent_id = self::get($charge, 'payment_intent', '');
$intent_id = is_string($intent_id) ? $intent_id : (string) self::get($intent_id, 'id', '');

$exists = self::find_imported_charge_order([ (string) self::get($charge, 'id', ''), $intent_id ]);
if ($exists) {
    return $exists;
}

return $charge;
