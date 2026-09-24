<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Secrets_Render_Trait
{
    /**
     * Renders HTML for the Maintenance tab security panel.
     *
     * @return string
     */
    public static function render_maintenance_secret_storage_html()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-render-render-maintenance-secret-storage-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Renders a compact encryption status panel for the getting started page.
     *
     * @return string
     */
    public static function render_getting_started_encryption_html()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-render-render-getting-started-encryption-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Shows admin notice after a manual migration run.
     *
     * @return void
     */
    public static function render_migration_admin_notice()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/secrets-render-render-migration-admin-notice.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Returns the wp-config encryption constant wrapped in a code element.
     *
     * @param bool $enabled Whether the constant should be true or false.
     * @return string
     */
    private static function format_encrypt_secrets_constant_html($enabled = true)
    {
        return '<code>define( \'PPCART_ENCRYPT_SECRETS\', ' . ($enabled ? 'true' : 'false') . ' );</code>';
    }
}
