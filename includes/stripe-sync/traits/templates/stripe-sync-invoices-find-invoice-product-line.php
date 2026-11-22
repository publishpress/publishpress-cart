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

// Invoices from before StudioCart or Cart added metadata, for example on an
// imported subscription, still belong here when this site already stores
// their Stripe subscription.
foreach ($lines as $line) {
    $subscription_item = self::get($line, 'subscription_item', '');
    if (! $subscription_item) {
        $subscription_item = self::path($line, [ 'parent', 'subscription_item_details', 'subscription_item' ], '');
    }
    if (! $subscription_item) {
        continue;
    }

    $line_subscription = self::get($line, 'subscription', '');
    if (! $line_subscription) {
        $line_subscription = self::path($line, [ 'parent', 'subscription_item_details', 'subscription' ], '');
    }
    if (! $line_subscription) {
        $line_subscription = self::get($invoice, 'subscription', '');
    }
    if (! $line_subscription) {
        $line_subscription = self::path($invoice, [ 'parent', 'subscription_details', 'subscription' ], '');
    }

    if (is_string($line_subscription) && 0 === strpos($line_subscription, 'sub_')) {
        $local_subscription = PPCart_Subscription::get_by_sub_id($line_subscription);
        if ($local_subscription && $local_subscription->id) {
            return $line;
        }
    }
}

return false;
