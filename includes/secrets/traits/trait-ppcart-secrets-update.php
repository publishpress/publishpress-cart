<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Secrets_Update_Trait
{
    /**
     * Sanitizes secret fields and preserves empty password submissions.
     *
     * @param mixed  $value       New value.
     * @param string $option_name Option name.
     * @return mixed
     */
    public static function sanitize_secret_field($value, $option_name = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-update-sanitize-secret-field.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Routes generic pre_update_option calls for sensitive options.
     *
     * @param mixed  $value     New value.
     * @param string $option    Option name.
     * @param mixed  $old_value Old value.
     * @return mixed
     */
    public static function filter_pre_update_any_option($value, $option, $old_value)
    {
        if (! self::is_sensitive_option((string) $option)) {
            return $value;
        }

        return self::filter_pre_update_sensitive_option($value, $old_value, (string) $option);
    }

    /**
     * Filters sensitive option updates: capability, empty-save, encryption.
     *
     * @param mixed  $value       New value.
     * @param mixed  $old_value   Old value.
     * @param string $option_name Option name.
     * @return mixed
     */
    public static function filter_pre_update_sensitive_option($value, $old_value, $option_name)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-update-filter-pre-update-sensitive-option.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Redacts sensitive tokens from free-form text (fallback when viewer unavailable).
     *
     * @param string $text Text.
     * @return string
     */
    public static function redact_text($text)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-update-redact-text.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Whether the current request should be blocked from changing secrets.
     *
     * @return bool
     */
    private static function should_block_sensitive_update()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-update-should-block-sensitive-update.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
