<?php

if (! defined('ABSPATH')) {
    exit;
}


if ('POST' !== strtoupper(self::get_server_value('REQUEST_METHOD'))) {
    return false;
}

$content_length = absint(self::get_server_value('CONTENT_LENGTH'));
if ($content_length > self::REQUEST_SIZE_LIMIT) {
    return false;
}

$content_type = strtolower(self::get_server_value('CONTENT_TYPE') ?: self::get_server_value('HTTP_CONTENT_TYPE'));
if ($content_type && false === strpos($content_type, 'json') && false === strpos($content_type, 'stripe')) {
    return false;
}

if (! $content_type && ! self::get_server_value('HTTP_STRIPE_SIGNATURE')) {
    return false;
}

$key = 'ppcart_stripe_webhook_rejected_' . self::sanitize_key($reason) . '_' . self::get_remote_ip_hash();
if (function_exists('get_transient') && get_transient($key)) {
    return false;
}

if (function_exists('set_transient')) {
    set_transient($key, 1, self::REJECTED_TTL);
}

return true;
