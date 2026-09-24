<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_debug_logger;

$order_post_id = absint($post_id);
if (! $order_post_id) {
    return;
}

$connect = PPCart_Public::get_stripe_connect_config();
if (! $connect['enabled'] || empty($connect['destination']) || $connect['total_fee_percent'] <= 0) {
    return;
}

$currency = is_string($currency) && '' !== $currency ? strtolower($currency) : strtolower((string) $ppcart_currency);
ppcart_update_post_meta($order_post_id, 'stripe_connect_fee_percent', (float) $connect['total_fee_percent']);

$fee_cents = 0;
$intent_id = is_string($intent_id) ? sanitize_text_field($intent_id) : '';

if ($intent_id && isset($ppcart_stripe['sk']) && ! empty($ppcart_stripe['sk'])) {
    try {
        $stripe = ppcart_stripe_client($ppcart_stripe['sk']);
        $intent = $stripe->paymentIntents->retrieve($intent_id);
        if (isset($intent->application_fee_amount) && null !== $intent->application_fee_amount) {
            $fee_cents = (int) $intent->application_fee_amount;
        }
    } catch (Exception $e) {
        $ppcart_debug_logger->log_debug('Stripe connect fee audit retrieval failed: ' . $e->getMessage(), 4);
    }
}

if ($fee_cents <= 0 && $amount_for_stripe > 0) {
    $fee_cents = (int) round(((float) $amount_for_stripe) * ($connect['total_fee_percent'] / 100));
    if ($fee_cents < 1) {
        $fee_cents = 1;
    }
}

if ($fee_cents <= 0) {
    return;
}

$fee_amount = (float) $fee_cents;
$zero_decimal_currency = ppcart_get_zero_decimal_currencies();
if (! in_array(strtoupper($currency), $zero_decimal_currency, true)) {
    $fee_amount = $fee_amount / 100;
}

ppcart_update_post_meta($order_post_id, 'stripe_connect_fee_amount_cents', $fee_cents);
ppcart_update_post_meta($order_post_id, 'stripe_connect_fee_amount', $fee_amount);
