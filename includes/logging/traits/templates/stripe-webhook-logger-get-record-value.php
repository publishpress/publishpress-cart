<?php

if (! defined('ABSPATH')) {
    exit;
}


if (is_object($record) && isset($record->$property) && '' !== (string) $record->$property) {
    return $record->$property;
}

if (function_exists('ppcart_get_post_meta')) {
    return ppcart_get_post_meta($post_id, $meta_key, true);
}

if (function_exists('get_post_meta')) {
    return get_post_meta($post_id, $meta_key, true);
}

return '';
