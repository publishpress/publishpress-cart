<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Secrets_Read_Trait
{
    /**
     * Reads raw option value from the database without decrypt filters.
     *
     * @param string $option_name Option name.
     * @return string
     */
    public static function get_raw_option_value($option_name)
    {
        global $wpdb;

        if (! isset($wpdb)) {
            return '';
        }

        $option_name = (string) $option_name;

        if ('' === $option_name) {
            return '';
        }

        $value = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads stored ciphertext directly, bypassing get_option() filters.
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                $option_name
            )
        );

        if (null === $value) {
            return '';
        }

        $value = maybe_unserialize($value);

        return is_string($value) ? $value : '';
    }

    /**
     * Returns a decrypted option value when stored encrypted.
     *
     * @param mixed $value Option value.
     * @return mixed
     */
    public static function maybe_decrypt_option_value($value)
    {
        if (! is_string($value) || ! self::is_encrypted_value($value)) {
            return $value;
        }

        $decrypted = self::decrypt_value($value);

        return false !== $decrypted ? $decrypted : $value;
    }

    /**
     * Reads a sensitive option and returns the decrypted plaintext value.
     *
     * @param string $option_name Option name.
     * @param mixed  $default     Default when missing.
     * @return mixed
     */
    public static function get_decrypted_option($option_name, $default = false)
    {
        $option_name = (string) $option_name;

        if ('' === $option_name) {
            return $default;
        }

        return self::handle_sensitive_option_read($option_name, get_option($option_name, $default));
    }

    /**
     * Decrypts a sensitive option on read and migrates plaintext storage when encryption is on.
     *
     * @param string $option_name Option name.
     * @param mixed  $value       Value from get_option or alloptions.
     * @return mixed
     */
    public static function handle_sensitive_option_read($option_name, $value)
    {
        $option_name = (string) $option_name;

        if ('' === $option_name || ! self::is_sensitive_option($option_name)) {
            return $value;
        }

        if (self::encryption_enabled() && self::encryption_available()) {
            self::migrate_plaintext_option($option_name);
        }

        return self::maybe_decrypt_option_value($value);
    }

    /**
     * Encrypts one plaintext sensitive option in the database when needed.
     *
     * @param string $option_name Option name.
     * @return bool Whether the stored value is encrypted afterward.
     */
    public static function migrate_plaintext_option($option_name)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-read-migrate-plaintext-option.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Decrypts encrypted values in the autoloaded options cache.
     *
     * Runs the decrypt scan at most once per request and skips entirely when
     * encryption is disabled. Latch is set before encryption_enabled() because
     * that call reads an option and re-enters this filter.
     *
     * @param array<string, mixed> $alloptions Autoloaded options.
     * @return array<string, mixed>
     */
    public static function filter_alloptions_decrypt($alloptions)
    {
        if (! is_array($alloptions)) {
            return $alloptions;
        }

        // 'alloptions' fires hundreds of times per request; scan at most once.
        if (self::$alloptions_decrypt_done) {
            return $alloptions;
        }

        self::$alloptions_decrypt_done = true;

        if (! self::encryption_enabled()) {
            return $alloptions;
        }

        $decrypted = self::decrypt_alloptions($alloptions);

        // Write back so later (latched) reads see plaintext — required for regex-only
        // sensitive names, which have no per-option option_<name> decrypt filter.
        if (function_exists('wp_cache_set')) {
            wp_cache_set('alloptions', $decrypted, 'options');
        }

        return $decrypted;
    }

    /**
     * Decrypts encrypted values in an alloptions array. No latch — always scans.
     *
     * Used by refresh_cached_secret_options() for explicit cache refresh.
     *
     * @param array<string, mixed> $alloptions Autoloaded options.
     * @return array<string, mixed>
     */
    public static function decrypt_alloptions($alloptions)
    {
        if (! is_array($alloptions)) {
            return $alloptions;
        }

        foreach ($alloptions as $option_name => $value) {
            if (! self::is_sensitive_option((string) $option_name) || ! is_string($value)) {
                continue;
            }

            $alloptions[$option_name] = self::maybe_decrypt_option_value($value);
        }

        return $alloptions;
    }
}
