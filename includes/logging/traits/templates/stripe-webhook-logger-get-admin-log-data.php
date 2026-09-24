<?php

if (! defined('ABSPATH')) {
    exit;
}


$file_name = self::get_log_file_name(false);
$log_path  = $file_name ? self::get_log_path(0, false) : '';
$exists    = $log_path && file_exists($log_path);
$size      = $exists ? (int) filesize($log_path) : 0;
$modified  = $exists ? (int) filemtime($log_path) : 0;
$entries   = self::read_entries([ 'limit' => $limit ]);
$lines     = [];

foreach ($entries as $entry) {
    $context = isset($entry['context']) && is_array($entry['context']) ? $entry['context'] : [];
    $lines[] = sprintf(
        '%1$s | %2$s | %3$s | %4$s | %5$s | %6$s | %7$s | %8$s',
        self::format_admin_time($entry),
        strtoupper((string) self::get($entry, 'status', '')),
        self::get($entry, 'type', ''),
        self::get($entry, 'object_id', ''),
        self::format_record_context($context),
        self::format_amount_context($context),
        self::format_customer_context($context),
        self::get($entry, 'message', '')
    );
}

return [
    'file_name'      => $file_name ? $file_name : __('Not created yet', 'publishpress-cart'),
    'exists'         => $exists,
    'size'           => $size,
    'size_label'     => self::format_file_size($size),
    'modified'       => $modified,
    'modified_label' => $modified ? sprintf(
        /* translators: %s is a human-readable time difference. */
        __('%s ago', 'publishpress-cart'),
        human_time_diff($modified, time())
    ) : __('Not written yet', 'publishpress-cart'),
    'entries'        => $entries,
    'preview'        => implode("\n", $lines),
    'view_url'       => self::nonce_url('admin.php?page=ppcart-settings&ppcart_view_stripe_webhook_log=1', 'ppcart_view_stripe_webhook_log', 'ppcart_view_stripe_webhook_log_nonce'),
    'download_url'   => self::nonce_url('admin.php?page=ppcart-settings&ppcart_download_stripe_webhook_log=1', 'ppcart_download_stripe_webhook_log', 'ppcart_download_stripe_webhook_log_nonce'),
    'clear_url'      => self::nonce_url('admin.php?page=ppcart-settings&ppcart_clear_stripe_webhook_log=1', 'ppcart_clear_stripe_webhook_log', 'ppcart_clear_stripe_webhook_log_nonce'),
];
