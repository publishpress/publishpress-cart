<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults = [
    'limit'     => self::DEFAULT_READ_LIMIT,
    'offset'    => 0,
    'bytes'     => self::DEFAULT_TAIL_BYTES,
    'level'     => '',
    'search'    => '',
    'date_from' => '',
    'date_to'   => '',
    'file_name' => '',
];
$args     = array_merge($defaults, is_array($args) ? $args : []);
$entries  = [];
$sequence = 0;
$paths    = self::get_log_paths(self::sanitize_file_name($args['file_name']));

foreach ($paths as $log_path) {
    if (! $log_path || ! is_readable($log_path)) {
        continue;
    }

    $lines = self::tail_lines($log_path, absint($args['bytes']));
    foreach ($lines as $line) {
        $entry = self::parse_line($line);
        if (! $entry || ! self::entry_matches($entry, $args)) {
            continue;
        }

        $entry['sequence'] = ++$sequence;
        $entries[] = $entry;
    }
}

$entries = array_reverse($entries);

$offset = max(0, absint($args['offset']));
$limit  = max(1, absint($args['limit']));

return array_slice($entries, $offset, $limit);
