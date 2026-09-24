<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Files_Repository_Trait::log_download().


global $wpdb;

if (!$file->download_id) {
    return false;
}

// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__ -- Stores requester IP for audit only.
$remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
$file->downloads[time()] = $remote_addr;

$args = [
    'downloads' => maybe_serialize($file->downloads),
    'downloads_remaining' => $file->downloads_remaining,
];

$args['downloads_remaining'] = (is_numeric($args['downloads_remaining'])) ? $args['downloads_remaining'] - 1 : 'unlimited';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updates log data for a single download row.
$updated = $wpdb->update(self::live_table(), $args, [ 'download_id' => $file->download_id ], [ '%s', '%s' ], [ '%d' ]);
if (false === $updated) {
    return false;
}

return true;
