<?php

if (! defined('ABSPATH')) {
    exit;
}


$bytes = absint($bytes);

if (function_exists('size_format')) {
    return size_format($bytes);
}

if ($bytes >= 1048576) {
    return round($bytes / 1048576, 1) . ' MB';
}

if ($bytes >= 1024) {
    return round($bytes / 1024, 1) . ' KB';
}

return $bytes . ' B';
