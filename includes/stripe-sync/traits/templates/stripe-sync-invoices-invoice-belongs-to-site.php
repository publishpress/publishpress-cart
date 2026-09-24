<?php

if (! defined('ABSPATH')) {
    exit;
}


$line = self::find_invoice_product_line($invoice);
if (! $line) {
    return false;
}

$origin = self::path($line, [ 'metadata', 'origin' ], '');
return ! $origin || $origin === get_site_url();
