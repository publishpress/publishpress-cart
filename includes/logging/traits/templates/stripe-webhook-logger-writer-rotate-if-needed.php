<?php

if (! defined('ABSPATH')) {
    exit;
}


$log_path = self::get_log_path();
if (! file_exists($log_path)) {
    return;
}

clearstatcache(true, $log_path);
$size = filesize($log_path);
if (false === $size || ($size + $append_size) <= self::max_file_size()) {
    return;
}

$oldest = self::get_log_path(self::MAX_ROTATED_FILES, false);
if ($oldest && file_exists($oldest)) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.WP.AlternativeFunctions.unlink_unlink -- Deletes oldest rotated plugin-owned log.
    unlink($oldest);
}

for ($i = self::MAX_ROTATED_FILES - 1; $i >= 1; $i--) {
    $source = self::get_log_path($i, false);
    $target = self::get_log_path($i + 1, false);
    if ($source && $target && file_exists($source)) {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename,WordPress.WP.AlternativeFunctions.rename_rename -- Atomically rotates plugin-owned log files.
        rename($source, $target);
    }
}

$first = self::get_log_path(1, false);
if ($first) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename,WordPress.WP.AlternativeFunctions.rename_rename -- Atomically rotates plugin-owned log file.
    rename($log_path, $first);
}
