<?php

if (! defined('ABSPATH')) {
    exit;
}


if (empty($file_name)) {
    $file_name = $this->default_log_file;
}

if (! $this->ensure_log_directory()) {
    return;
}

$debug_log_file = $this->get_log_file_path($file_name);
$lock_path      = $debug_log_file . '.lock';
$lock           = fopen($lock_path, 'c'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Creates plugin-owned debug log lock file.
if (! $lock) {
    return;
}

if (flock($lock, LOCK_EX)) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock -- Locks plugin-owned debug log writes.
    if (! $this->overwrite) {
        $this->rotate_if_needed($file_name, strlen($content));
    }

    $f_opts = $this->overwrite ? 'wb' : 'ab';
    $fp     = fopen($debug_log_file, $f_opts); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Writes plugin debug log file in wp-content.
    if ($fp) {
        fwrite($fp, $content); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writes plugin debug log content to file.
        fclose($fp); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned debug log handle.
    }
    flock($lock, LOCK_UN); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock -- Unlocks plugin-owned debug log writes.
}

fclose($lock); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned debug log lock.
$this->overwrite = false;
