<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! self::metadata($charge, 'ppcart_product_id') && ! self::get($charge, 'invoice')) {
    return false;
}

$origin = self::path($charge, [ 'metadata', 'origin' ], '');
if (! $origin) {
    $origin = self::path($charge, [ 'subscription_details', 'metadata', 'origin' ], '');
}

return ! $origin || $origin === get_site_url();
