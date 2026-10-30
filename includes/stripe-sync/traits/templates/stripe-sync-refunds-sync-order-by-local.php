<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! class_exists('PPCart_Stripe_Sync')) {
    throw new Exception(esc_html__('Stripe sync layer is unavailable.', 'publishpress-cart'));
}

$stripe_id = $order->stripe_charge_id ?? $order->transaction_id ?? '';
if (! $stripe_id) {
    throw new Exception(esc_html__('Stripe payment ID is missing for this order.', 'publishpress-cart'));
}

$stripe_mode = $order->gateway_mode ?? $order->stripe_mode ?? 'test';
$stripe = PPCart_Stripe_Sync::get_client_for_mode($stripe_mode);

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

$synced = PPCart_Stripe_Sync::sync_charge_status($charge, null, $stripe);
if (! $synced) {
    throw new Exception(esc_html__('Stripe charge could not be matched to this local order.', 'publishpress-cart'));
}

return __('Order synced successfully.', 'publishpress-cart');
