<?php

if (! defined('ABSPATH')) {
    exit;
}


$value = is_scalar($value) ? sanitize_text_field((string) $value) : '';

if ('' !== $value || '' === $option_name) {
    return $value;
}

$existing = class_exists('PPCart_Secrets')
    ? PPCart_Secrets::get_decrypted_option($option_name, '')
    : get_option($option_name, '');
if ('' !== $existing && is_string($existing)) {
    return $existing;
}

return $value;
