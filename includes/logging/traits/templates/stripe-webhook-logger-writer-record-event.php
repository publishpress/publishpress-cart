<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Stripe_Webhook_Logger_Writer_Trait::record_event().

$event_id        = self::sanitize_text((string) self::get($event, 'id', ''));
$original_status = self::sanitize_key($status);
$event_status    = self::normalize_status($original_status);

if ('' === $event_status) {
    return false;
}

if ($event_id && in_array($original_status, [ 'handled', 'not_applied' ], true) && isset(self::$event_final_statuses[ $event_id ])) {
    return false;
}

if ('ignored' === $event_status && ! self::include_ignored_events()) {
    if ($event_id) {
        self::$event_final_statuses[ $event_id ] = $event_status;
    }
    return false;
}

if (! self::is_enabled()) {
    return false;
}

$object = self::get(self::get($event, 'data'), 'object');
$entry  = [
    'timestamp' => time(),
    'time_utc'  => gmdate('c'),
    'event_id'  => $event_id,
    'type'      => self::sanitize_text((string) self::get($event, 'type', '')),
    'object'    => self::sanitize_text((string) self::get($object, 'object', '')),
    'object_id' => self::sanitize_text((string) self::get($object, 'id', '')),
    'livemode'  => (bool) self::get($event, 'livemode', false),
    'status'    => $event_status,
    'message'   => self::sanitize_text($message),
    'context'   => self::sanitize_context($context),
];

$written = self::append_entry($entry);
if ($written && $event_id) {
    self::$event_final_statuses[ $event_id ] = $event_status;
}

if (in_array($event_status, [ 'failed', 'rejected' ], true)) {
    self::log_debug('Stripe webhook ' . $event_status . ': ' . $entry['message'], 4);
}

return $written;
