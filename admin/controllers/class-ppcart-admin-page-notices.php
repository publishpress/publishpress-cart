<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Places WordPress notices inside Cart's settings and customer report layouts.
 *
 * WordPress renders notice hooks normally. The page script moves their DOM
 * nodes into our shell, preserving dismiss buttons and attached event handlers.
 * No output buffer is held across WordPress hooks.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Admin_Page_Notices
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'start_settings_notice_capture']);
        add_action('ppcart_settings_admin_notices', [$this, 'print_captured_settings_notices'], 5);
        add_action('ppcart_customer_report_admin_notices', [$this, 'print_captured_settings_notices'], 5);
    }

    private function is_custom_notice_shell_page()
    {
        return PPCart_Admin_Screens::is_settings_screen() || PPCart_Admin_Screens::is_customer_reports_screen();
    }

    /**
     * Retain the public entry point while moving notices without PHP buffering.
     */
    public function start_settings_notice_capture()
    {
        if (! $this->is_custom_notice_shell_page()) {
            return;
        }

        wp_enqueue_script(
            'ppcart-admin-page-notices',
            PPCART_BASE_URL . 'admin/js/ppcart-admin-page-notices.js',
            ['common'],
            PPCART_VERSION,
            true
        );
    }

    /**
     * Compatibility entry point: there is no longer a cross-hook buffer to close.
     */
    public function finish_settings_notice_capture()
    {
    }

    public function print_captured_settings_notices()
    {
        if ($this->is_custom_notice_shell_page()) {
            echo '<div class="ppcart-global-admin-notices"></div>';
        }
    }
}
