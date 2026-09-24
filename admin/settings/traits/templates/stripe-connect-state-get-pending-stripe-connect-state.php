<?php

if (! defined('ABSPATH')) {
    exit;
}


$user_id = get_current_user_id();
if (! $user_id) {
    return [];
}

$state = get_transient($this->get_stripe_connect_pending_user_transient_key($user_id, $mode));
if ('' === (string) $state) {
    return [];
}

$pending = get_transient($this->get_stripe_connect_pending_transient_key($state));
if (! is_array($pending)) {
    return [];
}

if (
    empty($pending['state'])
    || empty($pending['mode'])
    || empty($pending['user_id'])
    || (int) $pending['user_id'] !== (int) $user_id
    || (string) $pending['mode'] !== (string) $mode
) {
    return [];
}

return $pending;
