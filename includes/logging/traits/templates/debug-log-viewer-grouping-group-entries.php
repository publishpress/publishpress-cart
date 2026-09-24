<?php

if (! defined('ABSPATH')) {
    exit;
}


$groups = [];

foreach ($entries as $entry) {
    if (! is_array($entry)) {
        continue;
    }

    $key = self::get_group_key($entry);
    if (! isset($groups[ $key ])) {
        $groups[ $key ] = self::create_group($key);
    }

    $groups[ $key ]['entries'][] = $entry;
    $groups[ $key ]              = self::update_group_summary($groups[ $key ], $entry);
}

foreach ($groups as $key => $group) {
    usort($group['entries'], $compare_entries_oldest_first);
    $group['event_count'] = count($group['entries']);
    $group['title']       = self::get_group_title($group);
    $group['record']      = self::format_record_context($group['record_ids']);
    $groups[ $key ]       = $group;
}

$groups = array_values($groups);
usort($groups, $compare_groups_newest_first);

return $groups;
