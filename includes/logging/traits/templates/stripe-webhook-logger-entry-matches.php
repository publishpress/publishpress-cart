<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! empty($args['status']) && self::get($entry, 'status', '') !== $args['status']) {
    return false;
}

if (! empty($args['type']) && false === stripos(self::get($entry, 'type', ''), $args['type'])) {
    return false;
}

$timestamp = absint(self::get($entry, 'timestamp', 0));
if (! empty($args['date_from'])) {
    $date_from = strtotime(self::sanitize_text($args['date_from']) . ' 00:00:00');
    if ($date_from && $timestamp && $timestamp < $date_from) {
        return false;
    }
}

if (! empty($args['date_to'])) {
    $date_to = strtotime(self::sanitize_text($args['date_to']) . ' 23:59:59');
    if ($date_to && $timestamp && $timestamp > $date_to) {
        return false;
    }
}

if (! empty($args['search'])) {
    $haystack = implode(
        ' ',
        [
            self::get($entry, 'event_id', ''),
            self::get($entry, 'type', ''),
            self::get($entry, 'object', ''),
            self::get($entry, 'object_id', ''),
            self::get($entry, 'message', ''),
            self::json_encode(self::get($entry, 'context', [])),
        ]
    );

    if (false === stripos($haystack, $args['search'])) {
        return false;
    }
}

return true;
