<?php

if (! defined('ABSPATH')) {
    exit;
}


static $enqueued = false;

if ($enqueued) {
    return;
}

$enqueued = true;
$version  = defined('PPCART_VERSION') ? PPCART_VERSION : '1.0.0';
$base_url = defined('PPCART_BASE_URL') ? PPCART_BASE_URL : '';

if (function_exists('wp_enqueue_style') && $base_url) {
    wp_enqueue_style(
        'ppcart-debug-log-viewer',
        $base_url . 'admin/css/ppcart-debug-log-viewer.css',
        [],
        $version,
        'all'
    );
}

if (function_exists('wp_enqueue_script') && $base_url) {
    wp_enqueue_script(
        'ppcart-debug-log-viewer',
        $base_url . 'admin/js/ppcart-debug-log-viewer.js',
        [],
        $version,
        false
    );
}
