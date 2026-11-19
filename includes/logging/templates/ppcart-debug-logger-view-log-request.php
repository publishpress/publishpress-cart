<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce is checked before serving any log action below.
$current_page = isset($_REQUEST['page']) ? sanitize_key(wp_unslash($_REQUEST['page'])) : '';

if ('ppcart-settings' !== $current_page) {
    return;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These are route checks only; each log action verifies its own nonce below.
if (empty($_REQUEST['ppcart_view_log']) && empty($_REQUEST['ppcart_download_log'])) {
    return;
}

if (function_exists('current_user_can') && ! current_user_can('manage_options')) {
    wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'publishpress-cart'));
}

//View debug log
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a route check; ppcart_check_admin_referer() validates before serving the log.
if (! empty($_REQUEST['ppcart_view_log'])) {
    ppcart_check_admin_referer('ppcart_view_debug_log', 'ppcart_view_debug_log_nonce');
    if (class_exists('PPCart_Debug_Log_Viewer')) {
        PPCart_Debug_Log_Viewer::render_log_page($this->default_log_file);
        exit();
    }
    $this->view_log();
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a route check; ppcart_check_admin_referer() validates before serving the log.
if (! empty($_REQUEST['ppcart_download_log'])) {
    ppcart_check_admin_referer('ppcart_download_debug_log', 'ppcart_download_debug_log_nonce');
    $this->view_log();
}
