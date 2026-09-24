<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! self::is_enabled() || ! self::should_log_rejected_request($reason)) {
    return false;
}

$context = array_merge(
    [
        'reason'         => self::sanitize_key($reason),
        'method'         => self::get_server_value('REQUEST_METHOD'),
        'content_length' => absint(self::get_server_value('CONTENT_LENGTH')),
        'remote_ip_hash' => self::get_remote_ip_hash(),
    ],
    self::sanitize_context($context)
);

$entry = [
    'timestamp' => time(),
    'time_utc'  => gmdate('c'),
    'event_id'  => '',
    'type'      => 'stripe.webhook.request',
    'object'    => 'request',
    'object_id' => '',
    'livemode'  => false,
    'status'    => 'rejected',
    'message'   => self::format_rejected_message($reason),
    'context'   => $context,
];

$written = self::append_entry($entry);
if ($written) {
    self::log_debug('Stripe webhook rejected: ' . $entry['message'], 4);
}

return $written;
