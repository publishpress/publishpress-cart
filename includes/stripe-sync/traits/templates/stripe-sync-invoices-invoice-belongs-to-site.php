<?php

if (! defined('ABSPATH')) {
    exit;
}


$line = self::find_invoice_product_line($invoice);
if (! $line) {
    return false;
}

$origin = self::path($line, [ 'metadata', 'origin' ], '');
if (! $origin || $origin === get_site_url()) {
    return true;
}

// The origin keeps the URL the subscription was bought on, so it goes stale
// when the site moves to a new domain. An invoice for a Stripe subscription
// this site already stores still belongs here.
$sub_id = self::get($invoice, 'subscription', '');
if (! $sub_id) {
    $sub_id = self::get($line, 'subscription', '');
}
if (! $sub_id) {
    $sub_id = self::path($line, [ 'parent', 'subscription_item_details', 'subscription' ], '');
}
if (! $sub_id) {
    $sub_id = self::path($invoice, [ 'parent', 'subscription_details', 'subscription' ], '');
}

if (! $sub_id || ! is_string($sub_id)) {
    return false;
}

$local_sub = PPCart_Subscription::get_by_sub_id($sub_id);

return (bool) ($local_sub && $local_sub->id);
