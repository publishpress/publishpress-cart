<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Debug_Logger::__construct().

self::$instance        = $this;
$this->log_folder_path = self::resolve_log_dir();
//Check config and if debug is enabled then set the enabled flag to true
if ($enabled = get_option('_ppcart_enable_debug')) {//Debugging is enabled
    $this->debug_enabled = true;
}
$this->init_default_log_file();

add_action('init', [$this, 'view_log_request']);
add_filter('_ppcart_option_list', [$this, 'enable_debug_log_setting'], 999);
