<?php

if (! defined('ABSPATH')) {
    exit;
}


self::enqueue_assets();

if (function_exists('wp_print_styles')) {
    wp_print_styles([ 'ppcart-debug-log-viewer' ]);
    return;
}
