<?php

if (! defined('ABSPATH')) {
    exit;
}


$max_bytes = $max_bytes ? $max_bytes : self::DEFAULT_TAIL_BYTES;
$size      = filesize($path);
if (false === $size) {
    return [];
}

$handle = fopen($path, 'rb'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reads plugin-owned webhook log file.
if (! $handle) {
    return [];
}

if ($size > $max_bytes) {
    fseek($handle, -1 * $max_bytes, SEEK_END);
    fgets($handle);
}

$content = stream_get_contents($handle);
fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned webhook log file.

if (false === $content || '' === $content) {
    return [];
}

return preg_split('/\r\n|\r|\n/', $content);
