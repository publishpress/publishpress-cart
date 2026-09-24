<?php

if (! defined('ABSPATH')) {
    exit;
}


if (file_exists($path)) {
    return;
}

$handle = fopen($path, 'wb'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Creates plugin-owned log guard file.
if ($handle) {
    fwrite($handle, $content); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writes plugin-owned log guard file.
    fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned log guard file.
}
