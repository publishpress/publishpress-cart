<?php

if (! defined('ABSPATH')) {
    exit;
}


$files = $this->get_product_files($download->product_id);

$download->name = esc_html__('Sorry, this file is no longer available.', 'publishpress-cart');
$download->url = false;
$download->path = false;
$download->product_name = false;

if (isset($download->downloads) && $download->downloads) {
    $download->downloads = maybe_unserialize($download->downloads);
} else {
    $download->downloads = [];
}

$download->downloads_remaining = ($download->downloads_remaining == 'unlimited') ? '&infin;' : $download->downloads_remaining;
$download->download_expires ??= false;
$download->expires = (!isset($download->download_expires) || !$download->download_expires) ? '--' : date_i18n(get_option('date_format'), strtotime($download->download_expires));

if (is_countable($files)) {
    foreach ($files as $file) {
        if ($file['file_id'] == $download->file_id) {
            if (isset($file['file_hide']) && !$show_hidden) {
                break;
            }
            $download->file_redirect = $file['file_redirect'] ?? false;
            $download->name = $file['file_name'];
            $download->path = $file['file_url'];
            $download->url = site_url("/{$this->slug}/{$download->order_key}");
            $download->product_name = ppcart_get_public_product_name($download->product_id);
            break;
        }
    }
}

$download = apply_filters('ppcart_download', $download);

if (!$download->url) {
    return false;
}

return $download;
