<?php

if (! defined('ABSPATH')) {
    exit;
}


$user_id = get_current_user_id();
if (! $user_id || '' === $state) {
    return;
}

$pending = [
    'state'       => sanitize_text_field((string) $state),
    'mode'        => 'live' === $mode ? 'live' : 'test',
    'user_id'     => absint($user_id),
    'return_url'  => esc_url_raw((string) $return_url),
    'website_url' => esc_url_raw((string) $website_url),
    'created_at'  => time(),
    'encryption'  => [
        'algorithm'       => isset($key_pair['algorithm']) ? sanitize_key((string) $key_pair['algorithm']) : '',
        'public_key'      => isset($key_pair['public_key']) ? sanitize_textarea_field((string) $key_pair['public_key']) : '',
        'private_key'     => isset($key_pair['private_key']) ? (string) $key_pair['private_key'] : '',
        'public_key_hash' => isset($key_pair['public_key_hash']) ? sanitize_text_field((string) $key_pair['public_key_hash']) : '',
    ],
];

set_transient($this->get_stripe_connect_pending_transient_key($state), $pending, 15 * MINUTE_IN_SECONDS);
set_transient($this->get_stripe_connect_pending_user_transient_key($user_id, $mode), $state, 15 * MINUTE_IN_SECONDS);
set_transient($this->get_stripe_connect_pending_reference_transient_key($pending['encryption']['public_key_hash']), $state, 15 * MINUTE_IN_SECONDS);

$this->log_stripe_connect_debug('connect_pending_state_stored', [
    'mode'                => $pending['mode'],
    'state_prefix'        => substr((string) $pending['state'], 0, 8),
    'user_id'             => $user_id,
    'return_url'          => $pending['return_url'],
    'website_url'         => $pending['website_url'],
    'public_key_hash'     => substr((string) $pending['encryption']['public_key_hash'], 0, 12),
    'encryption_algorithm' => $pending['encryption']['algorithm'],
]);
