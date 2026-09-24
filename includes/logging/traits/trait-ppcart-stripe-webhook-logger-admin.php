<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Webhook_Logger_Admin_Trait
{
    /**
     * Add webhook log controls to the existing settings screen.
     *
     * @param array $options Settings option list.
     * @return array
     */
    public static function register_settings($options)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-admin-register-settings.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Handle nonce-protected admin log actions.
     *
     * @return void
     */
    public static function handle_admin_request()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-admin-handle-admin-request.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Enqueue assets for the standalone Stripe webhook log route.
     *
     * @return void
     */
    private static function enqueue_assets()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-admin-enqueue-assets.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Render standalone webhook log styles.
     *
     * @return void
     */
    private static function render_styles()
    {
        self::enqueue_assets();

        if (function_exists('wp_print_styles')) {
            wp_print_styles([ 'ppcart-stripe-webhook-log' ]);
            return;
        }
    }

    /**
     * Render details toggle script.
     *
     * @return void
     */
    private static function render_script()
    {
        self::enqueue_assets();

        if (function_exists('wp_print_scripts')) {
            wp_print_scripts([ 'ppcart-stripe-webhook-log' ]);
            return;
        }
    }

    /**
     * Render a simple admin table for recent webhook log rows.
     *
     * @return void
     */
    private static function render_log_page()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-admin-render-log-page.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Render the expanded details panel for one webhook row.
     *
     * @param array $row     Log row.
     * @param array $context Hydrated context.
     * @return string
     */
    private static function render_details_panel($row, $context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-admin-render-details-panel.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Render one label/value detail.
     *
     * @param string $label Label.
     * @param mixed  $value Value.
     * @param bool   $code  Whether to render the value as code.
     * @return string
     */
    private static function render_detail_item($label, $value, $code = false)
    {
        $value = '' === (string) $value ? '-' : (string) $value;
        $html  = '<div>';
        $html .= '<dt>' . esc_html($label) . '</dt>';
        $html .= '<dd>';
        $html .= $code ? '<code>' . esc_html($value) . '</code>' : esc_html($value);
        $html .= '</dd>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Build settings action links.
     *
     * @return string
     */
    private static function get_settings_links_note()
    {
        $view_url = self::nonce_url('admin.php?page=ppcart-settings&ppcart_view_stripe_webhook_log=1', 'ppcart_view_stripe_webhook_log', 'ppcart_view_stripe_webhook_log_nonce');
        $download_url = self::nonce_url('admin.php?page=ppcart-settings&ppcart_download_stripe_webhook_log=1', 'ppcart_download_stripe_webhook_log', 'ppcart_download_stripe_webhook_log_nonce');
        $clear_url = self::nonce_url('admin.php?page=ppcart-settings&ppcart_clear_stripe_webhook_log=1', 'ppcart_clear_stripe_webhook_log', 'ppcart_clear_stripe_webhook_log_nonce');

        return sprintf(
            '<a href="%1$s" rel="noopener noreferrer" data-testid="%7$s">%2$s</a> &nbsp; <a href="%3$s" rel="noopener noreferrer" data-testid="%8$s">%4$s</a> &nbsp; <a href="%5$s" rel="noopener noreferrer" data-testid="%9$s">%6$s</a>',
            esc_attr($view_url),
            esc_html__('view Stripe webhook log', 'publishpress-cart'),
            esc_attr($download_url),
            esc_html__('download log', 'publishpress-cart'),
            esc_attr($clear_url),
            esc_html__('delete log', 'publishpress-cart'),
            esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-note-view')),
            esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-note-download')),
            esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-note-delete'))
        );
    }

    /**
     * Add a nonce to an admin URL.
     *
     * @param string $path        Admin path.
     * @param string $action      Nonce action.
     * @param string $nonce_name  Nonce field name.
     * @return string
     */
    private static function nonce_url($path, $action, $nonce_name)
    {
        $url = function_exists('admin_url') ? admin_url($path) : $path;
        return function_exists('wp_nonce_url') ? wp_nonce_url($url, $action, $nonce_name) : $url;
    }
}
