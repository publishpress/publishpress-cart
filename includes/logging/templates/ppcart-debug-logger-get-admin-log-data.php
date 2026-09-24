<?php

if (! defined('ABSPATH')) {
    exit;
}

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Debug_Logger::get_admin_log_data().


$file_name = basename((string) get_option('_ppcart_log_file', 'log.txt'));
$log_dir   = self::resolve_log_dir();
$log_path  = $log_dir ? trailingslashit($log_dir) . $file_name : '';
$exists    = $log_path && file_exists($log_path);
$size      = $exists ? (int) filesize($log_path) : 0;
$modified  = $exists ? (int) filemtime($log_path) : 0;
$log_preview = '';
$truncated   = false;

if ($exists && $size > 0) {
    $max_bytes = 120000;
    $read_size = min($size, $max_bytes);
    $handle    = fopen($log_path, 'rb'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reads plugin-owned debug log file.

    if ($handle) {
        if ($size > $max_bytes) {
            fseek($handle, -$read_size, SEEK_END);
            $truncated = true;
        }

        $log_preview = (string) fread($handle, $read_size); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reads plugin-owned debug log file.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned debug log file handle opened for preview.
        fclose($handle);
    }

    $log_preview = function_exists('ppcart_redact_secrets_from_text')
        ? ppcart_redact_secrets_from_text($log_preview)
        : $log_preview;
    $lines       = preg_split("/\r\n|\n|\r/", trim($log_preview));

    if (is_array($lines) && count($lines) > 200) {
        $lines     = array_slice($lines, -200);
        $truncated = true;
    }

    $log_preview = is_array($lines) ? implode("\n", $lines) : $log_preview;
}

$bytes = (int) $size;
if (function_exists('size_format')) {
    $size_label = size_format($bytes);
} elseif ($bytes >= 1048576) {
    $size_label = round($bytes / 1048576, 1) . ' MB';
} elseif ($bytes >= 1024) {
    $size_label = round($bytes / 1024, 1) . ' KB';
} else {
    $size_label = $bytes . ' B';
}

$settings_page = class_exists('PPCart_Admin_Screens') ? PPCart_Admin_Screens::PAGE_SETTINGS : 'ppcart-settings';
$view_path     = 'admin.php?page=' . $settings_page . '&ppcart_view_log=1';
$download_path = 'admin.php?page=' . $settings_page . '&ppcart_download_log=1';
$clear_path    = 'admin.php?page=' . $settings_page . '&ppcart_reset_log=1';

$view_url = function_exists('admin_url') ? admin_url($view_path) : $view_path;
$view_url = function_exists('wp_nonce_url') ? wp_nonce_url($view_url, 'ppcart_view_debug_log', 'ppcart_view_debug_log_nonce') : $view_url;

$download_url = function_exists('admin_url') ? admin_url($download_path) : $download_path;
$download_url = function_exists('wp_nonce_url') ? wp_nonce_url($download_url, 'ppcart_download_debug_log', 'ppcart_download_debug_log_nonce') : $download_url;

$clear_url = function_exists('admin_url') ? admin_url($clear_path) : $clear_path;
$clear_url = function_exists('wp_nonce_url') ? wp_nonce_url($clear_url, 'ppcart_reset_debug_log', 'ppcart_reset_debug_log_nonce') : $clear_url;

return [
    'file_name'       => $file_name,
    'exists'          => $exists,
    'size'            => $size,
    'size_label'      => $size_label,
    'modified'        => $modified,
    'modified_label'  => $modified ? sprintf(
        /* translators: %s is a human-readable time difference. */
        __('%s ago', 'publishpress-cart'),
        human_time_diff($modified, time())
    ) : __('Not written yet', 'publishpress-cart'),
    'preview'         => $log_preview,
    'truncated'       => $truncated,
    'view_url'        => $view_url,
    'download_url'    => $download_url,
    'clear_url'       => $clear_url,
];
