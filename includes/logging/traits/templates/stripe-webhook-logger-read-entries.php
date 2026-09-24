<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults = [
    'limit'    => self::DEFAULT_READ_LIMIT,
    'offset'   => 0,
    'bytes'    => self::DEFAULT_TAIL_BYTES,
    'status'   => '',
    'type'     => '',
    'search'   => '',
    'date_from' => '',
    'date_to'   => '',
    'rotation' => 0,
];
$args     = array_merge($defaults, is_array($args) ? $args : []);
$log_path = self::get_log_path(absint($args['rotation']), false);

if (! $log_path || ! is_readable($log_path)) {
    return [];
}

$lines   = self::tail_lines($log_path, absint($args['bytes']));
$entries = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ('' === $line) {
        continue;
    }

    $entry = json_decode($line, true);
    if (! is_array($entry)) {
        $entry = [
            'timestamp' => 0,
            'time_utc'  => '',
            'event_id'  => '',
            'type'      => '',
            'object'    => '',
            'object_id' => '',
            'livemode'  => false,
            'status'    => 'failed',
            'message'   => 'Invalid log line.',
            'context'   => [],
        ];
    }

    if (self::is_noisy_received_entry($entry)) {
        continue;
    }

    $entry = self::hydrate_entry_context($entry);

    if (self::entry_matches($entry, $args)) {
        $entries[] = $entry;
    }
}

$entries = array_reverse($entries);
return array_slice($entries, absint($args['offset']), absint($args['limit']));
