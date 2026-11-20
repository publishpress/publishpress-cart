<?php

if (! defined('ABSPATH')) {
    exit;
}


$raw_status = sanitize_text_field((string) self::get($subscription, 'status', ''));
$status_map = [
    'active'             => 'active',
    'trialing'           => 'trialing',
    'past_due'           => 'past_due',
    'unpaid'             => 'unpaid',
    'incomplete'         => 'incomplete',
    'incomplete_expired' => 'canceled',
    'canceled'           => 'canceled',
    'paused'             => 'paused',
];

if (! isset($status_map[ $raw_status ])) {
    self::log_debug('Unknown Stripe subscription status mapped to past_due: ' . $raw_status, 4);
}

$local_status = $status_map[ $raw_status ] ?? 'past_due';

$pause_collection = self::get($subscription, 'pause_collection');
$pause_behavior = self::path($pause_collection, [ 'behavior' ], '');
if ('canceled' !== $local_status && 'void' === $pause_behavior) {
    $local_status = 'paused';
}

$cancel_at = absint(self::get($subscription, 'cancel_at', 0));
$current_period_end = absint(self::get($subscription, 'current_period_end', 0));

// Newer Stripe API versions carry current_period_end on the subscription item
// rather than the subscription. Without this fallback the value reads as zero and
// the stored next bill date is cleared on every sync.
if (! $current_period_end) {
    $current_period_end = absint(
        self::path($subscription, [ 'items', 'data', 0, 'current_period_end' ], 0)
    );
}

// cancel_at_period_end cancels at the end of the current period. Read it as a
// cancel_at on that date, in case Stripe leaves cancel_at empty.
if (! $cancel_at && 'canceled' !== $local_status && self::get($subscription, 'cancel_at_period_end', false)) {
    $cancel_at = $current_period_end;
}

$next_bill = $current_period_end;

if ($cancel_at || in_array($local_status, [ 'canceled', 'paused' ], true)) {
    $next_bill = '';
}

return [
    'status'             => $local_status,
    'sub_status'         => $raw_status,
    'raw_status'         => $raw_status,
    'sub_next_bill_date' => $next_bill,
    'cancel_at'          => $cancel_at,
];
