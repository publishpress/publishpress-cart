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

return false;
