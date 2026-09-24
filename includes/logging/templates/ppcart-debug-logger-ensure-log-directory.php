<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! is_dir($this->log_folder_path)) {
    if (function_exists('wp_mkdir_p')) {
        wp_mkdir_p($this->log_folder_path);
    } else {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir,WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Creates plugin-owned debug log directory in standalone tests.
        mkdir($this->log_folder_path, 0755, true);
    }
}

if (! is_dir($this->log_folder_path) || ! is_writable($this->log_folder_path)) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable,WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Checks plugin-owned debug log directory.
    return false;
}

$this->write_guard_file(
    $this->log_folder_path . '/index.html',
    ''
);

$legacy_php_guard = $this->log_folder_path . '/index.php';
if (is_file($this->log_folder_path . '/index.html') && is_file($legacy_php_guard)) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.WP.AlternativeFunctions.unlink_unlink -- Removes leftover generated PHP log guard after the HTML guard exists.
    unlink($legacy_php_guard);
}

return true;
