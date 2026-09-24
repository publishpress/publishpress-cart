<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Debug_Log_Viewer_Redaction_Trait
{
    /**
     * Redact sensitive scalar values recursively.
     *
     * @param mixed $context Context value.
     * @return mixed
     */
    public static function redact_context($context)
    {
        if (is_array($context)) {
            $redacted = [];
            foreach ($context as $key => $value) {
                $redacted[ $key ] = self::is_sensitive_key($key) ? '[redacted]' : self::redact_context($value);
            }
            return $redacted;
        }

        if (is_scalar($context) || null === $context) {
            return self::redact_text((string) $context);
        }

        return '[redacted]';
    }

    /**
     * Redact sensitive tokens from text.
     *
     * @param string $text Text.
     * @return string
     */
    public static function redact_text($text)
    {
        $patterns = [
            '/\bsk_(?:live|test)_[A-Za-z0-9_]+\b/' => 'sk_[redacted]',
            '/\bpk_(?:live|test)_[A-Za-z0-9_]+\b/' => 'pk_[redacted]',
            '/((?:secret|password|token|api[_ -]?key|authorization|signature|client_secret|payment_method|payment_method_id|nonce)\s*[:=]\s*)("[^"]+"|\'[^\']+\'|[^,\s]+)/i' => '$1[redacted]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), (string) $text);
    }

    /**
     * Check if a context key is sensitive.
     *
     * @param string|int $key Context key.
     * @return bool
     */
    public static function is_sensitive_key($key)
    {
        return (bool) preg_match('/card|token|secret|password|client_secret|payment_method|payment_method_id|api_key|authorization|stripe_account|nonce/i', (string) $key);
    }
}
