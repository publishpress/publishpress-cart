<?php

if (! defined('ABSPATH')) {
    exit;
}


$lines = self::path($invoice, [ 'lines', 'data' ], []);
if (! is_array($lines)) {
    return false;
}

foreach ($lines as $line) {
    $subscription_item = self::get($line, 'subscription_item', '');
    if (! $subscription_item) {
        $subscription_item = self::path($line, [ 'parent', 'subscription_item_details', 'subscription_item' ], '');
    }

    if (! $subscription_item) {
        continue;
    }

    if (
        self::metadata($line, 'ppcart_product_id')
        || self::metadata($line, 'ppcart_subscription_id')
        || self::metadata(self::path($line, [ 'plan' ]), 'ppcart_product_id')
        || self::metadata(self::path($line, [ 'price' ]), 'ppcart_product_id')
    ) {
        return $line;
    }
}

return false;
