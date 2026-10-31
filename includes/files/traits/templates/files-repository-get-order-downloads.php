<?php

defined('ABSPATH') || exit;

$order_downloads = $this->get_download_access_records($order_id, $args);

if ($order_downloads) {
    $downloads = [];
    foreach ($order_downloads as $download) {
        $show_hidden = ($args['status'] == 'all') ? true : false;
        if ($download = $this->setup_download($download, $show_hidden)) {
            $downloads[] = $download;
        }
    }
    return $downloads;
}

return false;
