<?php

if (! defined('ABSPATH')) {
    exit;
}


if (self::metadata($subscription, 'ppcart_subscription_id')) {
    return true;
}

$items = self::path($subscription, [ 'items', 'data' ], []);
if (! is_array($items)) {
    return false;
}

foreach ($items as $item) {
    if (self::metadata($item, 'ppcart_product_id') || self::metadata(self::path($item, [ 'plan' ]), 'ppcart_product_id') || self::metadata(self::path($item, [ 'price' ]), 'ppcart_product_id')) {
        return true;
    }
}

// Subscriptions bought before a store moved to Cart (for example from
// Studiocart) carry leftover metadata keys that only Compatibility Mode reads.
// A Stripe subscription this site already stores still belongs here.
$stripe_sub_id = self::get($subscription, 'id', '');
if (! $stripe_sub_id || ! is_string($stripe_sub_id)) {
    return false;
}

$local_sub = PPCart_Subscription::get_by_sub_id($stripe_sub_id);

return (bool) ($local_sub && $local_sub->id);
