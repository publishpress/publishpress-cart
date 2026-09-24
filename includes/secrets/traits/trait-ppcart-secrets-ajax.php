<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Secrets_Ajax_Trait
{
    /**
     * AJAX handler for toggling database encryption enablement.
     *
     * @return void
     */
    public static function ajax_set_encrypt_secrets()
    {
        ppcart_check_ajax_referer('ppcart_set_encrypt_secrets', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(
                [ 'message' => __('You do not have permission to change security encryption settings.', 'publishpress-cart') ],
                403
            );
        }

        if (self::is_encryption_controlled_by_constant()) {
            wp_send_json_error(
                [ 'message' => __('Encryption is controlled by PPCART_ENCRYPT_SECRETS in wp-config.php.', 'publishpress-cart') ]
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Encryption flag is consumed only after ppcart_check_ajax_referer() succeeds above.
        $enabled = isset($_POST['enabled']) && in_array(
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Encryption flag is consumed only after ppcart_check_ajax_referer() succeeds above.
            sanitize_text_field((string) wp_unslash($_POST['enabled'])),
            [ '1', 'true', 'yes' ],
            true
        );

        if (! self::set_db_encryption_enabled($enabled)) {
            wp_send_json_error(
                [ 'message' => __('Unable to update security encryption setting.', 'publishpress-cart') ]
            );
        }

        $migration = null;
        if ($enabled && self::has_plaintext_stored_credentials()) {
            $migration = self::migrate_plaintext_secrets();

            if (is_wp_error($migration)) {
                wp_send_json_error([ 'message' => $migration->get_error_message() ]);
            }
        }

        wp_send_json_success(
            [
                'message'         => $enabled
                    ? self::get_encryption_enabled_success_message($migration)
                    : __('Encryption disabled.', 'publishpress-cart'),
                'enabled'         => self::encryption_enabled(),
                'migration'       => $migration,
                'needs_migration' => $enabled && self::has_plaintext_stored_credentials(),
            ]
        );
    }

    /**
     * Success message after enabling encryption, including migration results when run.
     *
     * @param array<string, int>|null $migration Migration counts when migration ran.
     * @return string
     */
    private static function get_encryption_enabled_success_message($migration = null)
    {
        $message = __('Encryption enabled.', 'publishpress-cart');

        if (! is_array($migration)) {
            return $message;
        }

        $migrated = (int) ($migration['migrated'] ?? 0);
        $skipped  = (int) ($migration['skipped'] ?? 0);
        $failed   = (int) ($migration['failed'] ?? 0);

        if (0 === $migrated && 0 === $failed) {
            return $message;
        }

        if ($failed > 0) {
            return $message . ' ' . sprintf(
                /* translators: 1: migrated count, 2: skipped count, 3: failed count. */
                __('Encrypted %1$d stored credentials (%2$d skipped, %3$d failed). Use Encrypt stored credentials in Maintenance to retry failures.', 'publishpress-cart'),
                $migrated,
                $skipped,
                $failed
            );
        }

        return $message . ' ' . sprintf(
            /* translators: 1: migrated count, 2: skipped count. */
            __('Encrypted %1$d stored credentials (%2$d skipped).', 'publishpress-cart'),
            $migrated,
            $skipped
        );
    }

    /**
     * AJAX handler for manual secret migration.
     *
     * @return void
     */
    public static function ajax_migrate_secrets()
    {
        ppcart_check_ajax_referer('ppcart_migrate_secrets', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(
                [ 'message' => __('You do not have permission to encrypt stored credentials.', 'publishpress-cart') ],
                403
            );
        }

        $result = self::migrate_plaintext_secrets();

        if (is_wp_error($result)) {
            wp_send_json_error([ 'message' => $result->get_error_message() ]);
        }

        wp_send_json_success(
            [
                'message' => sprintf(
                    /* translators: 1: migrated count, 2: skipped count, 3: failed count. */
                    __('Migration complete. Encrypted: %1$d. Skipped: %2$d. Failed: %3$d.', 'publishpress-cart'),
                    (int) $result['migrated'],
                    (int) $result['skipped'],
                    (int) $result['failed']
                ),
                'counts'  => $result,
            ]
        );
    }

    /**
     * Dismisses the migration notice when requested.
     *
     * @return void
     */
    public static function maybe_dismiss_migration_notice()
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a route check; ppcart_check_admin_referer() validates before dismissing the notice.
        if (empty($_GET['ppcart_dismiss_secrets_notice'])) {
            return;
        }

        ppcart_check_admin_referer('ppcart_dismiss_secrets_notice');

        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, self::MIGRATION_DISMISS_META, '1');
        }

        delete_transient(self::MIGRATION_NOTICE_TRANSIENT);

        wp_safe_redirect(remove_query_arg([ 'ppcart_dismiss_secrets_notice', '_wpnonce' ]));
        exit;
    }
}
