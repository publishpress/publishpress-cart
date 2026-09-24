<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $path || ! is_readable($path)) {
    return [];
}

$size      = filesize($path);
$max_bytes = max(1, absint($max_bytes));
if (false === $size || 0 === $size) {
    return [];
}

$handle = fopen($path, 'rb'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reads plugin-owned debug log file.
if (! $handle) {
    return [];
}

if ($size > $max_bytes) {
    fseek($handle, -$max_bytes, SEEK_END);
    fgets($handle);
}

$content = stream_get_contents($handle);
fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned debug log handle.

if (false === $content) {
    return [];
}

return preg_split("/\r\n|\n|\r/", trim($content));
