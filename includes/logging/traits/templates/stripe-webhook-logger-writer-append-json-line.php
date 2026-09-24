<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! self::ensure_log_directory()) {
    return false;
}

$log_path  = self::get_log_path();
$lock_path = $log_path . '.lock';
$lock      = fopen($lock_path, 'c'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Creates plugin-owned lock file.
if (! $lock) {
    return false;
}

$written = false;
if (flock($lock, LOCK_EX)) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock -- Locks plugin-owned webhook log writes.
    self::rotate_if_needed(strlen($line) + 1);
    $handle = fopen($log_path, 'ab'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Appends to plugin-owned webhook log.
    if ($handle) {
        $written = false !== fwrite($handle, $line . PHP_EOL); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writes plugin-owned webhook log line.
        fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned webhook log handle.
    }
    flock($lock, LOCK_UN); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock -- Unlocks plugin-owned webhook log writes.
}

fclose($lock); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned lock handle.
return $written;
