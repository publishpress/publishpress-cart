<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Files_Admin_Trait::update_order_downloads().

global $wpdb;

// leave if not on the post edit screen
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is validated immediately after this early context guard.
if (! ppcart_is_order_post_type($post->post_type) || !is_admin() || !isset($_POST['original_publish']) || !isset($_POST['ppcart_process_downloads'])) {
    return;
}

if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-post_' . $post_id)) {
    return;
}

if (! current_user_can('edit_post', $post_id)) {
    return;
}

if (wp_is_post_revision($post_id) || $post->post_status == 'auto-draft') {
    return;
}

remove_action('save_post_' . $post->post_type, [$this,'update_order_downloads'], 99);

if (!$files = $this->get_order_downloads($post_id, ['status' => 'all'])) {
    return;
}

// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Bulk array is unslashed and each value is sanitized before use.
$posted_expires = isset($_POST['expires']) && is_array($_POST['expires']) ? wp_unslash($_POST['expires']) : [];
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Bulk array is unslashed and each value is sanitized before use.
$posted_remaining = isset($_POST['remaining']) && is_array($_POST['remaining']) ? wp_unslash($_POST['remaining']) : [];

foreach ($files as $download) {
    $changes = [];

    $_expires_raw = isset($posted_expires[ $download->download_id ]) ? sanitize_text_field($posted_expires[ $download->download_id ]) : '';
    $_expires = $_expires_raw ? date_i18n('Y-m-d H:i:s', strtotime($_expires_raw)) : '';
    $_remaining = (isset($posted_remaining[ $download->download_id ]) && '' !== (string) $posted_remaining[ $download->download_id ]) ? intval($posted_remaining[ $download->download_id ]) : 'unlimited';

    if (!$_expires && $download->download_expires) {
        $changes['download_expires'] = null;
    } elseif ($_expires && $_expires != $download->download_expires) {
        $changes['download_expires'] = $_expires;
    }

    if ($_remaining != $download->downloads_remaining) {
        $changes['downloads_remaining'] = $_remaining;
    }

    if (!empty($changes)) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updates download counters/expiry for a single download row.
        $wpdb->update(self::live_table(), $changes, [ 'download_id' => $download->download_id ], [ '%s', '%s' ], [ '%d' ]);
    }
}

add_action('save_post_' . $post->post_type, [$this,'update_order_downloads'], 99, 2);
