<?php

if (! defined('ABSPATH')) {
    exit;
}


$ppcart_files = new PPCart_Files();
$key = sanitize_text_field(get_query_var(PPCart_Files::DOWNLOAD_QUERY_VAR));

$download = $ppcart_files->get_download_by_key($key);

$check_login = get_option('ppcart_login_to_download');
if ($check_login && !is_user_logged_in()) {
    if ($pid = get_option('_ppcart_myaccount_page_id')) {
        wp_safe_redirect(get_permalink($pid));
        exit;
    } else {
        esc_html_e('Unauthorized, please log in to download.', 'publishpress-cart');
    }
    exit;
}

if ($check_login && $download) {
    $order_user_id = absint(ppcart_get_post_meta($download->order_id, 'user_account', true));
    $current_user_id = get_current_user_id();

    if (! current_user_can('manage_options') && (! $order_user_id || $order_user_id !== $current_user_id)) {
        wp_die(esc_html__('You are not authorized to download this file.', 'publishpress-cart'), 403);
    }
}

if ($download) {
    do_action('ppcart_before_show_download', $download);

    PPCart_Files::log_download($download);

    if (!$download->file_redirect) {
        $file_path = PPCart_Files::resolve_local_download_path($download->path);

        if (false === $file_path) {
            wp_die(
                esc_html__('Invalid file path.', 'publishpress-cart'),
                esc_html__('File Error', 'publishpress-cart'),
                [ 'response' => 403 ]
            );
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        ob_end_clean();
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streams a verified local download file to the authenticated buyer.
        readfile($file_path);
    } else {
        $redirect_url = esc_url_raw($download->path);
        $parsed       = wp_parse_url($redirect_url);
        $site_host    = wp_parse_url(home_url(), PHP_URL_HOST);
        $allowed_hosts = apply_filters('ppcart_download_allowed_redirect_hosts', [ $site_host ]);

        if (empty($parsed['host']) || in_array($parsed['host'], $allowed_hosts, true)) {
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            wp_die(
                esc_html__('Invalid file redirect destination.', 'publishpress-cart'),
                esc_html__('File Error', 'publishpress-cart'),
                [ 'response' => 400 ]
            );
        }
    }

    exit;
} else {
    esc_html_e('Sorry, this file is no longer available.', 'publishpress-cart');
    exit;
}
