<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Secrets_Migration_Trait
{
    /**
     * Returns migration and encryption status for the Maintenance UI.
     *
     * @return array<string, mixed>
     */
    public static function get_migration_status()
    {
        $plaintext = 0;
        $encrypted = 0;
        $empty     = 0;

        foreach (self::get_sensitive_option_names() as $option_name) {
            $raw = self::get_raw_option_value($option_name);

            if ('' === $raw) {
                ++$empty;
                continue;
            }

            if (self::is_encrypted_value($raw)) {
                ++$encrypted;
            } else {
                ++$plaintext;
            }
        }

        $last_migration = get_option(self::MIGRATION_DONE_OPTION, '');

        return [
            'encryption_enabled'        => self::encryption_enabled(),
            'encryption_available'      => self::encryption_available(),
            'encryption_key_source'     => self::get_encryption_key_source(),
            'encryption_control_source' => self::get_encryption_control_source(),
            'constant_defined'          => self::is_encryption_controlled_by_constant(),
            'db_encryption_enabled'     => self::is_db_encryption_enabled(),
            'auth_key_available'        => defined('AUTH_KEY') && '' !== AUTH_KEY,
            'plaintext_count'      => $plaintext,
            'encrypted_count'      => $encrypted,
            'empty_count'          => $empty,
            'has_plaintext_secrets' => $plaintext > 0,
            'last_migration'       => is_numeric($last_migration) ? (int) $last_migration : 0,
            'needs_migration'      => self::encryption_enabled() && $plaintext > 0,
        ];
    }

    /**
     * Whether any sensitive option has a non-empty plaintext value in the database.
     *
     * @return bool
     */
    public static function has_plaintext_stored_credentials()
    {
        return self::get_migration_status()['has_plaintext_secrets'];
    }

    /**
     * User-facing notice when plaintext credentials exist and encryption is off or pending migration.
     *
     * @param array<string, mixed> $status Migration status from get_migration_status().
     * @return string Empty when no notice is needed.
     */
    public static function get_plaintext_migration_notice($status = null)
    {
        $status = is_array($status) ? $status : self::get_migration_status();
        $count  = (int) ($status['plaintext_count'] ?? 0);

        if ($count <= 0) {
            return '';
        }

        if (! empty($status['encryption_enabled'])) {
            return sprintf(
                /* translators: %d: number of plaintext credentials. */
                _n(
                    'Encryption is enabled but %d stored credential is still in plaintext. Use Encrypt stored credentials to retry.',
                    'Encryption is enabled but %d stored credentials are still in plaintext. Use Encrypt stored credentials to retry.',
                    $count,
                    'publishpress-cart'
                ),
                $count
            );
        }

        return sprintf(
            /* translators: %d: number of plaintext credentials. */
            _n(
                'You have %d stored credential in plaintext. Enabling encryption will encrypt it automatically.',
                'You have %d stored credentials in plaintext. Enabling encryption will encrypt them automatically.',
                $count,
                'publishpress-cart'
            ),
            $count
        );
    }

    /**
     * Encrypts existing plaintext sensitive options (manual operator action).
     *
     * @param array<string, mixed> $args Arguments.
     * @return array<string, int>|WP_Error
     */
    public static function migrate_plaintext_secrets($args = [])
    {
        if (! self::encryption_enabled()) {
            return new WP_Error(
                'encryption_disabled',
                __('Encryption is not enabled. Enable it in Cart → Settings → Maintenance or add define( \'PPCART_ENCRYPT_SECRETS\', true ); to wp-config.php.', 'publishpress-cart')
            );
        }

        if (! self::encryption_available()) {
            return new WP_Error(
                'encryption_unavailable',
                __('Encryption requires AUTH_KEY or PPCART_SECRETS_KEY in wp-config.php.', 'publishpress-cart')
            );
        }

        $counts = [
            'migrated' => 0,
            'skipped'  => 0,
            'failed'   => 0,
        ];

        foreach (self::get_sensitive_option_names() as $option_name) {
            if (self::migrate_plaintext_option($option_name)) {
                ++$counts['migrated'];
                continue;
            }

            $raw = self::get_raw_option_value($option_name);

            if ('' === $raw) {
                ++$counts['skipped'];
                continue;
            }

            if (self::is_encrypted_value($raw)) {
                ++$counts['skipped'];
                continue;
            }

            ++$counts['failed'];
        }

        if (0 === $counts['failed']) {
            update_option(self::MIGRATION_DONE_OPTION, time());
        }

        set_transient(self::MIGRATION_NOTICE_TRANSIENT, $counts, defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400);

        return $counts;
    }
}
