<?php

if (! defined('ABSPATH')) {
    exit;
}


$stripe_enabled = (bool) get_option('_ppcart_stripe_enable');
$active_mode = $this->get_stripe_mode();
$connect_server_url = $this->get_stripe_connect_server_url();
$notice_html = '';
$notice = get_transient('ppcart_stripe_connect_admin_notice');
if (is_array($notice) && ! empty($notice['message'])) {
    if (isset($notice['type']) && 'success' === $notice['type']) {
        $notice_class = 'notice-success';
    } elseif (isset($notice['type']) && 'warning' === $notice['type']) {
        $notice_class = 'notice-warning';
    } else {
        $notice_class = 'notice-error';
    }
    $notice_html = '<div class="notice inline ' . esc_attr($notice_class) . '"><p>' . esc_html((string) $notice['message']) . '</p></div>';
    delete_transient('ppcart_stripe_connect_admin_notice');
}

ob_start();
echo '<div class="ppcart-stripe-connect">';
echo wp_kses_post($notice_html);

foreach ([ 'test', 'live' ] as $stripe_mode) {
    echo '<div class="ppcart-stripe-connect__mode" data-pp-stripe-connect-mode="' . esc_attr($stripe_mode) . '"' . ($stripe_mode === $active_mode ? '' : ' hidden') . '>';
    echo wp_kses(
        $this->get_stripe_connect_mode_status_html($stripe_mode, $stripe_enabled, $connect_server_url),
        PPCart_Admin_Stripe_Webhook_Settings::augment_allowed_html(wp_kses_allowed_html('post'))
    );
    echo '</div>';
}

echo '</div>';

return ob_get_clean();
