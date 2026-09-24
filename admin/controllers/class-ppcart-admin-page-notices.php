<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Buffers WordPress admin notices so settings and customer report pages can print them inside their page layout.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Admin_Page_Notices
{
    /** @var bool */
    private $settings_notice_buffer_active = false;

    /** @var int */
    private $settings_notice_buffer_level = 0;

    /** @var string */
    private $settings_captured_admin_notices = '';

    public function __construct()
    {
        add_action('network_admin_notices', [$this, 'start_settings_notice_capture'], -999999);
        add_action('user_admin_notices', [$this, 'start_settings_notice_capture'], -999999);
        add_action('admin_notices', [$this, 'start_settings_notice_capture'], -999999);
        add_action('all_admin_notices', [$this, 'finish_settings_notice_capture'], 999999);
        add_action('ppcart_settings_admin_notices', [$this, 'print_captured_settings_notices'], 5);
        add_action('ppcart_customer_report_admin_notices', [$this, 'print_captured_settings_notices'], 5);
    }

    private function is_custom_notice_shell_page()
    {
        return PPCart_Admin_Screens::is_settings_screen() || PPCart_Admin_Screens::is_customer_reports_screen();
    }
    public function start_settings_notice_capture()
    {
        if (! $this->is_custom_notice_shell_page() || $this->settings_notice_buffer_active) {
            return;
        }

        $this->settings_notice_buffer_active = true;
        $this->settings_notice_buffer_level  = ob_get_level() + 1;

        ob_start();
    }


    public function finish_settings_notice_capture()
    {
        if (! $this->settings_notice_buffer_active) {
            return;
        }

        $this->settings_notice_buffer_active = false;

        if (ob_get_level() !== $this->settings_notice_buffer_level) {
            $this->settings_notice_buffer_level = 0;
            return;
        }

        $captured_notices = (string) ob_get_clean();
        $this->settings_notice_buffer_level = 0;

        if ('' !== trim($captured_notices)) {
            $this->settings_captured_admin_notices .= $captured_notices;
        }
    }


    public function print_captured_settings_notices()
    {
        $this->finish_settings_notice_capture();

        if ('' === trim($this->settings_captured_admin_notices)) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Captured notices are already rendered by WordPress/admin notice callbacks.
        echo $this->settings_captured_admin_notices;
        $this->settings_captured_admin_notices = '';
    }
}
