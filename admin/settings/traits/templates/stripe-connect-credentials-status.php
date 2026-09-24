<?php

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('ppcart_get_stripe_platform_credentials_status')) {
    return ppcart_get_stripe_platform_credentials_status($mode);
}

$secret_key = $this->get_stripe_secret_key_for_mode($mode);

return [
    'mode'               => $mode,
    'credentials'        => [
        'mode' => $mode,
        'pk'   => '',
        'sk'   => $secret_key,
    ],
    'is_direct'          => '' !== $secret_key,
    'is_usable'          => '' !== $secret_key,
    'has_raw_keys'       => '' !== $secret_key,
    'requires_reconnect' => '' === $secret_key,
    'is_legacy_filtered' => false,
];
