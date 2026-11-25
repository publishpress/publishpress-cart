<?php

if (! defined('ABSPATH')) {
    exit;
}


$origin = self::path($charge, [ 'metadata', 'origin' ], '');
if (! $origin) {
    $origin = self::path($charge, [ 'subscription_details', 'metadata', 'origin' ], '');
}

if ($origin && $origin !== get_site_url()) {
    return false;
}

if (self::metadata($charge, 'ppcart_product_id') || self::get($charge, 'invoice')) {
    return true;
}

if (PPCART_CHECKOUT_TYPE_CART === self::metadata($charge, 'ppcart_checkout_type')) {
    return (bool) self::find_order_for_charge($charge);
}

// Stripe API versions from 2025-03-31 drop the charge's invoice field, so a
// subscription renewal charge has neither check above. A charge that a local
// order already stores as its payment belongs to this site.
$charge_id = (string) self::get($charge, 'id', '');
if ($charge_id && PPCart_Order::get_by_trans_id($charge_id)) {
    return true;
}

$payment_intent = (string) self::get($charge, 'payment_intent', '');
return $payment_intent && PPCart_Order::get_by_trans_id($payment_intent);
