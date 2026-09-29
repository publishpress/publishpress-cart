<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Secrets_Config_Trait
{
    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    public static function init()
    {
        if (self::$initialized) {
            self::register_option_decrypt_filters();

            return;
        }

        self::$initialized = true;

        add_filter('pre_update_option', [ __CLASS__, 'filter_pre_update_any_option' ], 10, 3);
        // Decrypt at read time through option_{$name} filters. Never rewrite core's
        // alloptions/options cache with plaintext: a persistent object cache would
        // then hold decrypted credentials.
        add_filter('pre_option', [ __CLASS__, 'filter_pre_option_register_decrypt' ], 10, 2);

        self::register_option_decrypt_filters();

        add_action('plugins_loaded', [ __CLASS__, 'register_option_decrypt_filters' ], 999);
        add_action('init', [ __CLASS__, 'refresh_cached_secret_options' ], 0);

        add_action('admin_notices', [ __CLASS__, 'render_migration_admin_notice' ]);
        add_action('admin_init', [ __CLASS__, 'maybe_dismiss_migration_notice' ]);
        add_action('wp_ajax_ppcart_migrate_secrets', [ __CLASS__, 'ajax_migrate_secrets' ]);
        add_action('wp_ajax_ppcart_set_encrypt_secrets', [ __CLASS__, 'ajax_set_encrypt_secrets' ]);
    }

    /**
     * Registers per-option decrypt filters for known sensitive option names.
     *
     * Safe to call multiple times as integrations register additional names.
     *
     * @return void
     */
    public static function register_option_decrypt_filters()
    {
        // Rebuild the memoized list so late ppcart_sensitive_option_names registrations are picked up.
        self::$sensitive_option_names_cache = null;
        self::$pre_option_checked           = [];

        foreach (self::get_sensitive_option_names() as $option_name) {
            self::register_option_decrypt_filter($option_name);
        }
    }

    /**
     * Registers the per-option decrypt filter for one sensitive option name.
     *
     * @param string $option_name Option name.
     * @return void
     */
    public static function register_option_decrypt_filter($option_name)
    {
        $option_name = (string) $option_name;

        if ('' === $option_name || isset(self::$decrypt_filters_registered[ $option_name ])) {
            return;
        }

        add_filter(
            'option_' . $option_name,
            static function ($value) use ($option_name) {
                return self::handle_sensitive_option_read($option_name, $value);
            },
            999,
            1
        );
        self::$decrypt_filters_registered[ $option_name ] = true;
    }

    /**
     * Adds a decrypt filter for sensitive names that are not in the static registry.
     *
     * Names that match only a sensitive pattern (for example `_ppcart_<x>_api_key`)
     * get their option_{$name} filter the first time get_option() asks for them.
     * The filtered value is never changed here.
     *
     * @param mixed  $pre         Short-circuit value from earlier pre_option filters.
     * @param string $option_name Option name.
     * @return mixed
     */
    public static function filter_pre_option_register_decrypt($pre, $option_name = '')
    {
        $option_name = (string) $option_name;

        if (
            '' === $option_name
            || self::$pre_option_running
            || isset(self::$decrypt_filters_registered[ $option_name ])
            || isset(self::$pre_option_checked[ $option_name ])
        ) {
            return $pre;
        }

        // is_sensitive_option() runs public filters that can call get_option() again.
        self::$pre_option_running                 = true;
        self::$pre_option_checked[ $option_name ] = true;

        if (self::is_sensitive_option($option_name)) {
            self::register_option_decrypt_filter($option_name);
        }

        self::$pre_option_running = false;

        return $pre;
    }

    /**
     * Removes plaintext secrets that earlier versions wrote into the options object cache.
     *
     * Earlier versions stored decrypted values in core's `alloptions` and per-option
     * cache entries. With a persistent object cache (Redis, Memcached) that kept
     * plaintext credentials outside the database. This only deletes stale entries,
     * so WordPress reloads the stored ciphertext. It never writes plaintext.
     *
     * @return void
     */
    public static function refresh_cached_secret_options()
    {
        if (! function_exists('wp_cache_get') || ! function_exists('wp_cache_delete')) {
            return;
        }

        if (! self::encryption_available()) {
            return;
        }

        $alloptions = wp_cache_get('alloptions', 'options');
        if (is_array($alloptions)) {
            foreach ($alloptions as $option_name => $value) {
                if (self::is_stale_plaintext_cache_value((string) $option_name, $value)) {
                    wp_cache_delete('alloptions', 'options');
                    break;
                }
            }
        }

        foreach (self::get_sensitive_option_names() as $option_name) {
            $cached = wp_cache_get($option_name, 'options');

            if (self::is_stale_plaintext_cache_value($option_name, $cached)) {
                wp_cache_delete($option_name, 'options');
            }
        }
    }

    /**
     * Whether a cached option value is plaintext while the database holds ciphertext.
     *
     * Plaintext that is also plaintext in the database is encrypted in place, so the
     * cache then holds ciphertext and later requests skip it.
     *
     * @param string $option_name Option name.
     * @param mixed  $value       Cached value.
     * @return bool
     */
    private static function is_stale_plaintext_cache_value($option_name, $value)
    {
        if (! is_string($value) || '' === $value || self::is_encrypted_value($value)) {
            return false;
        }

        if (! self::is_sensitive_option($option_name)) {
            return false;
        }

        if (self::migrate_plaintext_option($option_name)) {
            return false;
        }

        return self::is_encrypted_value(self::get_raw_option_value($option_name));
    }

    /**
     * Whether PPCART_ENCRYPT_SECRETS in wp-config controls encryption (DB flag ignored).
     *
     * @return bool
     */
    public static function is_encryption_controlled_by_constant()
    {
        return defined('PPCART_ENCRYPT_SECRETS');
    }

    /**
     * Whether encryption-at-rest is enabled.
     *
     * When PPCART_ENCRYPT_SECRETS is defined in wp-config.php it takes precedence
     * and the database flag is ignored.
     *
     * @return bool
     */
    public static function encryption_enabled()
    {
        if (self::is_encryption_controlled_by_constant()) {
            return (bool) constant('PPCART_ENCRYPT_SECRETS');
        }

        return self::is_db_encryption_enabled();
    }

    /**
     * Reads the database encryption flag without applying the wp-config override.
     *
     * @return bool
     */
    public static function is_db_encryption_enabled()
    {
        return self::option_is_enabled_flag(get_option(self::ENCRYPT_SECRETS_OPTION, false));
    }

    /**
     * Updates the database encryption flag (ignored when wp-config constant is set).
     *
     * @param bool $enabled Whether encryption should be enabled.
     * @return bool
     */
    public static function set_db_encryption_enabled($enabled)
    {
        if (self::is_encryption_controlled_by_constant()) {
            return false;
        }

        return update_option(self::ENCRYPT_SECRETS_OPTION, $enabled ? '1' : '0');
    }

    /**
     * Returns how encryption enablement is controlled.
     *
     * @return string constant|database|disabled
     */
    public static function get_encryption_control_source()
    {
        if (self::is_encryption_controlled_by_constant()) {
            return self::encryption_enabled() ? 'constant' : 'constant_disabled';
        }

        return self::is_db_encryption_enabled() ? 'database' : 'disabled';
    }

    /**
     * Whether encryption can run (enabled and key material available).
     *
     * @return bool
     */
    public static function encryption_available()
    {
        return self::encryption_enabled() && '' !== self::get_encryption_key();
    }

    /**
     * Returns known sensitive option names plus filter extensions.
     *
     * @return string[]
     */
    public static function get_sensitive_option_names()
    {
        if (null !== self::$sensitive_option_names_cache) {
            return self::$sensitive_option_names_cache;
        }

        $names = [
            '_ppcart_api_key',
            '_ppcart_stripe_live_sk',
            '_ppcart_stripe_test_sk',
            '_ppcart_stripe_live_webhook_secret',
            '_ppcart_stripe_test_webhook_secret',
            '_ppcart_paypal_secret',
            '_ppcart_paypal_sandbox_secret',
            '_ppcart_paypal_pdt_token',
            '_ppcart_paypal_sandbox_pdt_token',
            '_ppcart_mailchimp_api',
            '_ppcart_activecampaign_secret_key',
            '_ppcart_member_vault_api_key',
            '_ppcart_sendfox_api_key',
            '_ppcart_converkit_api',
            '_ppcart_converkit_secret_key',
            '_ppcart_googlerecaptchav2_site_secret',
            '_ppcart_googlerecaptchav3_site_secret',
            '_ppcart_drip_api_key',
            '_ppcart_mailerlite_api_key',
            '_ppcart_gurucan_api_key',
            '_ppcart_heartbeat_api_key',
            '_ppcart_upcoach_api_token',
            '_ppcart_mslms_api_key',
            '_ppcart_encharge_write_key',
            '_ppcart_teachable_password',
            '_ppcart_download_signing_key',
        ];

        /**
         * Extend the list of sensitive wp_options managed by PPCart_Secrets.
         *
         * @param string[] $names Option names.
         */
        $names = apply_filters('ppcart_sensitive_option_names', $names);

        self::$sensitive_option_names_cache = array_values(array_unique(array_filter(array_map('strval', (array) $names))));

        return self::$sensitive_option_names_cache;
    }

    /**
     * Checks whether an option name is sensitive.
     *
     * @param string $option_name Option name.
     * @return bool
     */
    public static function is_sensitive_option($option_name)
    {
        $option_name = (string) $option_name;

        if (self::ENCRYPT_SECRETS_OPTION === $option_name) {
            // Control flag, not a credential. Keep the public filter but default false
            // so a /secret/ matcher cannot encrypt the switch itself.
            return (bool) apply_filters('ppcart_is_sensitive_option', false, $option_name);
        }

        $registry_names = self::get_sensitive_option_names();

        $is_sensitive = in_array($option_name, $registry_names, true);

        if (! $is_sensitive && 0 === strpos($option_name, '_ppcart_')) {
            $patterns = [
                '/^_ppcart_.*_api_key$/',
                '/^_ppcart_.*_api_token$/',
                '/^_ppcart_.*_access_token$/',
                '/^_ppcart_.*_signature_key$/',
                '/^_ppcart_.*_secret(_|$)/i',
                '/^_ppcart_.*_password$/',
                '/^_ppcart_.*_write_key$/',
                '/^_ppcart_.*_webhook_data$/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $option_name)) {
                    $is_sensitive = true;
                    break;
                }
            }
        }

        /**
         * Whether an option name stores a payment or integration secret.
         *
         * @param bool   $is_sensitive Whether the option is sensitive.
         * @param string $option_name  Option name.
         */
        return (bool) apply_filters('ppcart_is_sensitive_option', $is_sensitive, $option_name);
    }

    /**
     * Whether a stored option value is an explicit on/true enable flag.
     *
     * Ciphertext leftover from a misclassified option must not count as enabled.
     *
     * @param mixed $value Raw option value.
     * @return bool
     */
    private static function option_is_enabled_flag($value)
    {
        if (is_string($value) && self::is_encrypted_value($value)) {
            $decrypted = self::decrypt_value($value);
            $value     = false !== $decrypted ? $decrypted : false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return 1 === $value;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower($value), [ '1', 'true', 'yes', 'on' ], true);
    }
}
