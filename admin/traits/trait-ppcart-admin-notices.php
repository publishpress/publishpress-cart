<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Admin notice shell helpers for settings and reports screens.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Notices_Trait
{
    /**
     * Query arg for persisted admin-notice dismissal.
     */
    public const DISMISS_ADMIN_NOTICE_QUERY_ARG = 'ppcart_dismiss_admin_notice';

    /**
     * Allowed dismiss keys for non-blocking notices.
     *
     * @var array<int, string>
     */
    private static $dismissible_admin_notice_keys = [ 'stripe_connect_success' ];

    /**
     * Checks whether the current admin request is the PublishPress Cart settings page.
     *
     * @return bool
     */
    private function is_settings_page()
    {
        return PPCart_Admin_Screens::is_settings_screen();
    }

    /**
     * Checks whether the current admin request is a PublishPress Cart reports page.
     *
     * @param bool $include_customer_reports Whether to include the customer reports page.
     * @return bool
     */
    private function is_reports_page($include_customer_reports = true)
    {
        return PPCart_Admin_Screens::is_reports_screen('', $include_customer_reports);
    }

    /**
     * Checks whether the current admin request is the customer report page.
     *
     * @return bool
     */
    private function is_customer_report_page()
    {
        return PPCart_Admin_Screens::is_customer_reports_screen();
    }

    /**
     * Checks whether this page renders captured notices inside a custom shell.
     *
     * @return bool
     */
    private function is_custom_notice_shell_page()
    {
        return $this->is_settings_page() || $this->is_customer_report_page();
    }

    /**
     * Whether payment/integration notices may render for the current request.
     *
     * Global admin_notices stays limited to Cart screens. Custom shell hooks
     * still render on settings/customer-report pages.
     *
     * @param string $filter Optional current filter override for tests.
     * @return bool
     */
    private function should_render_admin_notices($filter = '')
    {
        $filter = '' !== $filter
            ? (string) $filter
            : (function_exists('current_filter') ? (string) current_filter() : '');

        if (in_array($filter, [ 'ppcart_settings_admin_notices', 'ppcart_customer_report_admin_notices' ], true)) {
            return true;
        }

        return PPCart_Admin_Screens::is_plugin_screen();
    }

    /**
     * User-meta key for a dismissible admin notice.
     *
     * @param string $key Notice key.
     * @return string
     */
    private function get_admin_notice_dismiss_meta_key($key)
    {
        return '_ppcart_dismiss_admin_notice_' . sanitize_key($key);
    }

    /**
     * Whether the current user dismissed a non-blocking notice.
     *
     * @param string $key Notice key.
     * @return bool
     */
    private function is_admin_notice_dismissed($key)
    {
        $user_id = get_current_user_id();
        if (! $user_id) {
            return false;
        }

        return (bool) get_user_meta($user_id, $this->get_admin_notice_dismiss_meta_key($key), true);
    }

    /**
     * Nonce-protected dismiss URL for a non-blocking notice.
     *
     * @param string $key Notice key.
     * @return string
     */
    private function get_admin_notice_dismiss_url($key)
    {
        $key = sanitize_key($key);

        return wp_nonce_url(
            add_query_arg(self::DISMISS_ADMIN_NOTICE_QUERY_ARG, $key),
            'ppcart_dismiss_admin_notice_' . $key
        );
    }

    /**
     * Persists per-user dismissal for allowed non-blocking admin notices.
     *
     * @return void
     */
    public function maybe_dismiss_admin_notices()
    {
        if (! is_admin() || (! current_user_can('manage_options') && ! (function_exists('ppcart_user_can') && ppcart_user_can('manager_option')))) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Route check; ppcart_check_admin_referer() validates before writing user meta.
        if (empty($_GET[ self::DISMISS_ADMIN_NOTICE_QUERY_ARG ])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Key is sanitized; nonce is verified below.
        $key = sanitize_key(wp_unslash((string) $_GET[ self::DISMISS_ADMIN_NOTICE_QUERY_ARG ]));
        if (! in_array($key, self::$dismissible_admin_notice_keys, true)) {
            return;
        }

        ppcart_check_admin_referer('ppcart_dismiss_admin_notice_' . $key);

        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, $this->get_admin_notice_dismiss_meta_key($key), '1');
        }

        wp_safe_redirect(remove_query_arg([ self::DISMISS_ADMIN_NOTICE_QUERY_ARG, '_wpnonce' ]));
        exit;
    }

    /**
     * Prints captured global admin notices inside the settings shell.
     *
     * @return void
     */
    public function admin_notices()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/notices-admin-notices.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
