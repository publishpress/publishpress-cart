<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Stripe_Connect_Render_Trait
{
    private function get_stripe_connect_mode_status_html($mode, $stripe_enabled, $connect_server_url)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-render-get-stripe-connect-mode-status-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_stripe_connect_settings_html()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-render-get-stripe-connect-settings-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
