<?php

if (! defined('ABSPATH')) {
    exit;
}


$authenticated_user = wp_get_current_user();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is explicitly verified below for this admin action.
$revoke_id = isset($_GET['ppcart-revoke']) ? absint(wp_unslash($_GET['ppcart-revoke'])) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is explicitly verified below for this admin action.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is explicitly verified below for this admin action.
$key = isset($_GET['dl']) ? sanitize_text_field(wp_unslash($_GET['dl'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce value is verified before write operations.
$nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

if (! current_user_can('manage_options') && ! ppcart_user_can('manage_orders')) {
    return;
}

if ($current_post_id && ! current_user_can('edit_post', $current_post_id)) {
    return;
}

if ($revoke_id && $current_post_id && $key && $nonce && wp_verify_nonce($nonce, 'update-post_' . $current_post_id)) {
    $redirect = get_edit_post_link($current_post_id, 'edit');
    if ($download = $this->get_download_by_key($key, ['status' => 'all'])) {
        if ($this->revoke_access($revoke_id)) {
            /* translators: 1: file name, 2: user login. */
            ppcart_log_entry($current_post_id, sprintf(__('Access to file "%1$s" revoked by %2$s', 'publishpress-cart'), $download->name, $authenticated_user->user_login));
            $redirect .= '&ppcart-revoked=' . urlencode($download->name);
        } else {
            $redirect .= '&ppcart-revoked=error';
        }
    } else {
        $redirect .= '&ppcart-revoked=download-not-found';
    }

    ppcart_redirect($redirect);
}
