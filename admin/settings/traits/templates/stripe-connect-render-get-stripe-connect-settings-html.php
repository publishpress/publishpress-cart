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

$ppcart_stripe_settings_buffer_level = ob_get_level();
$ppcart_stripe_settings_buffer_active = true;
$ppcart_stripe_settings_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_stripe_settings_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_stripe_settings_buffer_active = false;
    }
    return $buffer;
});
try {
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
} catch (Throwable $ppcart_stripe_settings_buffer_exception) {
    $ppcart_stripe_settings_buffer_error = $ppcart_stripe_settings_buffer_exception;
} finally {
    $ppcart_stripe_settings_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_stripe_settings_buffer_active && ob_get_level() > $ppcart_stripe_settings_buffer_level + 1) {
        $ppcart_stripe_settings_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_stripe_settings_buffer_exception) {
            $ppcart_stripe_settings_buffer_error = $ppcart_stripe_settings_buffer_error ?? $ppcart_stripe_settings_buffer_exception;
            if (ob_get_level() >= $ppcart_stripe_settings_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_stripe_settings_buffer_active && ob_get_level() === $ppcart_stripe_settings_buffer_level + 1) {
        $ppcart_stripe_settings_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_stripe_settings_buffer_error) {
    throw $ppcart_stripe_settings_buffer_error;
}
return $ppcart_stripe_settings_buffer_output;
