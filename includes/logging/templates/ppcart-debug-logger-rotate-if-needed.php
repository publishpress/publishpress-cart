<?php

if (! defined('ABSPATH')) {
    exit;
}


$debug_log_file = $this->get_log_file_path($file_name);
if (! file_exists($debug_log_file)) {
    return;
}

clearstatcache(true, $debug_log_file);
$size = filesize($debug_log_file);
if (false === $size || ($size + $append_size) <= $this->max_file_size()) {
    return;
}

$oldest = $this->get_rotated_file_path($file_name, self::MAX_ROTATED_FILES);
if (file_exists($oldest)) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.WP.AlternativeFunctions.unlink_unlink -- Deletes oldest rotated plugin-owned debug log.
    unlink($oldest);
}

for ($i = self::MAX_ROTATED_FILES - 1; $i >= 1; $i--) {
    $source = $this->get_rotated_file_path($file_name, $i);
    $target = $this->get_rotated_file_path($file_name, $i + 1);
    if (file_exists($source)) {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename,WordPress.WP.AlternativeFunctions.rename_rename -- Atomically rotates plugin-owned debug log.
        rename($source, $target);
    }
}

$first = $this->get_rotated_file_path($file_name, 1);
// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename,WordPress.WP.AlternativeFunctions.rename_rename -- Atomically rotates plugin-owned debug log.
rename($debug_log_file, $first);
