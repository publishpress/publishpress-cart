<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! class_exists('PPCart_Stripe_Sync')) {
    throw new Exception(esc_html__('Stripe sync layer is unavailable.', 'publishpress-cart'));
}

$stripe_id = PPCart_Stripe_Sync::get_order_sync_payment_id($order);
if (! $stripe_id) {
    throw new Exception(esc_html__('Stripe payment ID is missing for this order.', 'publishpress-cart'));
}

if (! $stripe) {
    // An order migrated with no mode uses the store's current Stripe mode.
    $stripe_mode = ppcart_normalize_stripe_mode(! empty($order->gateway_mode) ? $order->gateway_mode : ($order->stripe_mode ?? ''));
    $stripe = PPCart_Stripe_Sync::get_client_for_mode($stripe_mode);
}

if (0 === strpos((string) $stripe_id, 'pi_')) {
    $payment_intent = $stripe->paymentIntents->retrieve(
        $stripe_id,
        [
            'expand' => [ 'latest_charge' ],
        ]
    );
    $charge = PPCart_Stripe_Sync::get($payment_intent, 'latest_charge', '');
    if (is_string($charge) && $charge) {
        $charge = $stripe->charges->retrieve($charge);
    }
} else {
    $charge = $stripe->charges->retrieve($stripe_id);
}

if (empty($charge)) {
    throw new Exception(esc_html__('Stripe charge could not be loaded for this order.', 'publishpress-cart'));
}

$charge_status = (string) PPCart_Stripe_Sync::get($charge, 'status', 'succeeded');
if ('succeeded' !== $charge_status || false === PPCart_Stripe_Sync::get($charge, 'paid', true)) {
    throw new Exception(esc_html__('The Stripe payment for this order has not succeeded, so the order was not changed.', 'publishpress-cart'));
}

// The charge comes from this order's own payment ID, so it belongs here even
// without Cart metadata (for example a payment imported from another plugin).
$synced = PPCart_Stripe_Sync::sync_charge_status($charge, null, $stripe, true);
if (! $synced) {
    throw new Exception(esc_html__('Stripe charge could not be matched to this local order.', 'publishpress-cart'));
}

// Fees are extra data. The order sync stays a success when Stripe has none yet.
if (class_exists('PPCart_Stripe_Fees')) {
    PPCart_Stripe_Fees::fetch((int) $synced->id, $stripe);
}

if (PPCart_Stripe_Sync::refund_needs_check()) {
    return __('Order synced. The refund needs a check: see the order log.', 'publishpress-cart');
}

return __('Order synced successfully.', 'publishpress-cart');
