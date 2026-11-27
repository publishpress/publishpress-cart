<?php

defined('ABSPATH') || exit;

$args = wp_parse_args($args, ['status' => 'active']);
$order_downloads = $this->get_download_access_records($order_id, $args);

if ($order_downloads) {
    $downloads = [];
    foreach ($order_downloads as $download) {
        if ($download = $this->setup_download($download)) {
            $downloads[] = $download;
        }
    }
    return $downloads;
}

return false;
