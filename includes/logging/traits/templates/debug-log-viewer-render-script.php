<?php

if (! defined('ABSPATH')) {
    exit;
}


self::enqueue_assets();

if (function_exists('wp_print_scripts')) {
    wp_print_scripts([ 'ppcart-debug-log-viewer' ]);
    return;
}
