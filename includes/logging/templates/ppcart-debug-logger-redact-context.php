<?php

if (! defined('ABSPATH')) {
    exit;
}


if (class_exists('PPCart_Debug_Log_Viewer')) {
    return PPCart_Debug_Log_Viewer::redact_context($context);
}

$redacted = [];
foreach ((array) $context as $key => $value) {
    if (preg_match('/card|token|secret|password|client_secret|payment_method|payment_method_id|api_key|authorization|stripe_account|nonce/i', (string) $key)) {
        $redacted[ $key ] = '[redacted]';
        continue;
    }

    if (is_array($value)) {
        $redacted[ $key ] = $this->redact_context($value);
    } elseif (is_scalar($value) || null === $value) {
        $redacted[ $key ] = function_exists('ppcart_redact_secrets_from_text')
            ? ppcart_redact_secrets_from_text((string) $value)
            : $value;
    }
}

return $redacted;
