<?php

/**
 * Helper wrappers for PPCart_Secrets.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
     * Checks whether a wp_option name stores a payment or integration secret.
     *
     * @param string $option_name Option name.
     * @return bool
     */
function ppcart_is_sensitive_option($option_name)
{
    return PPCart_Secrets::is_sensitive_option($option_name);
}


/**
     * Reads a sensitive wp_option and returns the decrypted plaintext value.
     *
     * @param string $option_name Option name.
     * @param mixed  $default     Default when missing.
     * @return mixed
     */
function ppcart_get_sensitive_option($option_name, $default = false)
{
    return PPCart_Secrets::get_decrypted_option($option_name, $default);
}


/**
     * Persists a sensitive wp_option (encryption applies via PPCart_Secrets when enabled).
     *
     * @param string $option_name Option name.
     * @param mixed  $value       Value to store.
     * @return bool
     */
function ppcart_set_sensitive_option($option_name, $value)
{
    if (PPCart_Secrets::is_sensitive_option($option_name)) {
        $value = PPCart_Secrets::sanitize_secret_field($value, $option_name);
    }

    return update_option($option_name, $value);
}


/**
     * Redacts sensitive tokens from log or preview text.
     *
     * @param string $text Text.
     * @return string
     */
function ppcart_redact_secrets_from_text($text)
{
    return PPCart_Secrets::redact_text((string) $text);
}
