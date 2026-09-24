<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These are route checks only; each log action verifies its own nonce below.
if (empty($_REQUEST['ppcart_view_stripe_webhook_log']) && empty($_REQUEST['ppcart_clear_stripe_webhook_log']) && empty($_REQUEST['ppcart_download_stripe_webhook_log'])) {
    return;
}

if (function_exists('current_user_can') && ! current_user_can('manage_options')) {
    wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'publishpress-cart'));
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a route check; ppcart_check_admin_referer() validates before clearing logs.
if (! empty($_REQUEST['ppcart_clear_stripe_webhook_log'])) {
    ppcart_check_admin_referer('ppcart_clear_stripe_webhook_log', 'ppcart_clear_stripe_webhook_log_nonce');
    self::clear_log_files();
    if (function_exists('wp_safe_redirect')) {
        wp_safe_redirect(admin_url('admin.php?page=ppcart-settings&ppcart_stripe_webhook_log_cleared=1'));
        exit();
    }
    return;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a route check; ppcart_check_admin_referer() validates before downloading logs.
if (! empty($_REQUEST['ppcart_download_stripe_webhook_log'])) {
    ppcart_check_admin_referer('ppcart_download_stripe_webhook_log', 'ppcart_download_stripe_webhook_log_nonce');
    self::download_log();
    exit();
}

ppcart_check_admin_referer('ppcart_view_stripe_webhook_log', 'ppcart_view_stripe_webhook_log_nonce');
self::render_log_page();
exit();
