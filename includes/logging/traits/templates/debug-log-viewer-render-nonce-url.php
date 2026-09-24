<?php

if (! defined('ABSPATH')) {
    exit;
}


$url = function_exists('admin_url') ? admin_url($path) : $path;
return function_exists('wp_nonce_url') ? wp_nonce_url($url, $action, $nonce_name) : $url;
