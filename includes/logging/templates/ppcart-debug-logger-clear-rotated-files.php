<?php

if (! defined('ABSPATH')) {
    exit;
}


for ($i = 1; $i <= self::MAX_ROTATED_FILES; $i++) {
    $rotated_file_path = $this->get_rotated_file_path($file_name, $i);
    if (file_exists($rotated_file_path)) {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.WP.AlternativeFunctions.unlink_unlink -- Deletes rotated plugin-owned debug log during reset.
        unlink($rotated_file_path);
    }
}
