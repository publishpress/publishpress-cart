<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! empty($args['level']) && self::normalize_level($entry['level']) !== self::normalize_level($args['level'])) {
    return false;
}

$timestamp = absint($entry['timestamp']);
if (! empty($args['date_from'])) {
    $date_from = strtotime(sanitize_text_field($args['date_from']) . ' 00:00:00');
    if ($date_from && $timestamp && $timestamp < $date_from) {
        return false;
    }
}

if (! empty($args['date_to'])) {
    $date_to = strtotime(sanitize_text_field($args['date_to']) . ' 23:59:59');
    if ($date_to && $timestamp && $timestamp > $date_to) {
        return false;
    }
}

if (! empty($args['search'])) {
    $haystack = implode(
        ' ',
        [
            $entry['time_display'],
            $entry['level'],
            $entry['workflow'],
            $entry['message'],
            $entry['record'],
            implode(' ', $entry['stripe_ids']),
            self::json_encode($entry['context']),
            $entry['raw'],
        ]
    );

    if (false === stripos($haystack, $args['search'])) {
        return false;
    }
}

return true;
