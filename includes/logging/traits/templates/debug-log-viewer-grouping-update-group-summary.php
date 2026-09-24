<?php

if (! defined('ABSPATH')) {
    exit;
}


$timestamp = absint(self::get($entry, 'timestamp', 0));
$sequence  = absint(self::get($entry, 'sequence', 0));

if (! $group['first_timestamp'] || ($timestamp && $timestamp < $group['first_timestamp'])) {
    $group['first_timestamp'] = $timestamp;
}
if (! $group['last_timestamp'] || $timestamp > $group['last_timestamp']) {
    $group['last_timestamp'] = $timestamp;
}
if (! $group['first_sequence'] || ($sequence && $sequence < $group['first_sequence'])) {
    $group['first_sequence'] = $sequence;
}
if (! $group['last_sequence'] || $sequence > $group['last_sequence']) {
    $group['last_sequence'] = $sequence;
    $group['summary']       = self::get($entry, 'message', '');
}

foreach ([ 'order_id', 'subscription_id', 'product_id' ] as $id_key) {
    if (empty($group['record_ids'][ $id_key ]) && ! empty($entry[ $id_key ])) {
        $group['record_ids'][ $id_key ] = absint($entry[ $id_key ]);
    }
}

$group['level']    = self::stronger_level($group['level'], self::get($entry, 'level', 'UNKNOWN'));
$group['workflow'] = self::choose_group_workflow($group['workflow'], self::get($entry, 'workflow', 'General'));

return $group;
