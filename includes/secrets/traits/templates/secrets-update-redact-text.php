<?php

if (! defined('ABSPATH')) {
    exit;
}


if (class_exists('PPCart_Debug_Log_Viewer')) {
    return PPCart_Debug_Log_Viewer::redact_text((string) $text);
}

$patterns = [
    '/\bsk_(?:live|test)_[A-Za-z0-9_]+\b/' => 'sk_[redacted]',
    '/\bpk_(?:live|test)_[A-Za-z0-9_]+\b/' => 'pk_[redacted]',
    '/((?:secret|password|token|api[_ -]?key|authorization|signature|client_secret|payment_method|payment_method_id|nonce)\s*[:=]\s*)("[^"]+"|\'[^\']+\'|[^,\s]+)/i' => '$1[redacted]',
];

return preg_replace(array_keys($patterns), array_values($patterns), (string) $text);
