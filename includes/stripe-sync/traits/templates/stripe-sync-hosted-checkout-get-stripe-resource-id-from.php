<?php

if (! defined('ABSPATH')) {
    exit;
}


if (is_string($resource)) {
    return sanitize_text_field($resource);
}

return sanitize_text_field((string) self::get($resource, 'id', ''));
