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

$posted_expires = [];
if (isset($_POST['expires']) && is_array($_POST['expires'])) {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Container traversal only; keys and scalar values are unslashed and sanitized below.
    foreach ($_POST['expires'] as $download_id => $expires) {
        if (! is_scalar($expires)) {
            continue;
        }

        $download_id = sanitize_text_field(wp_unslash((string) $download_id));
        $posted_expires[ $download_id ] = sanitize_text_field(wp_unslash((string) $expires));
    }
}

$posted_remaining = [];
if (isset($_POST['remaining']) && is_array($_POST['remaining'])) {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Container traversal only; keys and scalar values are unslashed and sanitized below.
    foreach ($_POST['remaining'] as $download_id => $remaining) {
        if (! is_scalar($remaining)) {
            continue;
        }

        $download_id = sanitize_text_field(wp_unslash((string) $download_id));
        $posted_remaining[ $download_id ] = sanitize_text_field(wp_unslash((string) $remaining));
    }
}

$updates = [];
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
        $updates[(int) $download->download_id] = $changes;
    }
}

foreach (array_chunk($updates, 200, true) as $batch) {
    $set_clauses = [];
    $values = [self::live_table()];
    foreach (['download_expires', 'downloads_remaining'] as $column) {
        $column_updates = [];
        foreach ($batch as $download_id => $changes) {
            if (array_key_exists($column, $changes)) {
                $column_updates[$download_id] = $changes[$column];
            }
        }
        if (! $column_updates) {
            continue;
        }

        $clause = '%i = CASE %i';
        $values[] = $column;
        $values[] = 'download_id';
        foreach ($column_updates as $download_id => $value) {
            $clause .= ' WHEN %d THEN ' . (null === $value ? 'NULL' : '%s');
            $values[] = $download_id;
            if (null !== $value) {
                $values[] = $value;
            }
        }
        $set_clauses[] = $clause . ' ELSE %i END';
        $values[] = $column;
    }

    $sql = 'UPDATE %i SET ' . implode(', ', $set_clauses) . ' WHERE download_id IN (' . implode(',', array_fill(0, count($batch), '%d')) . ')';
    $values = array_merge($values, array_keys($batch));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- SQL contains fixed column names and generated placeholders; all row values are prepared below.
    $wpdb->query($wpdb->prepare($sql, $values));
}

add_action('save_post_' . $post->post_type, [$this,'update_order_downloads'], 99, 2);
