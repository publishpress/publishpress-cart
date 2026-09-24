<?php

if (!defined('ABSPATH')) {
    exit;
}

if (empty($pending['state']) || empty($pending['mode']) || empty($pending['user_id'])) {
    return;
}

delete_transient($this->get_stripe_connect_pending_transient_key((string) $pending['state']));
delete_transient($this->get_stripe_connect_pending_user_transient_key((int) $pending['user_id'], (string) $pending['mode']));
if (! empty($pending['encryption']['public_key_hash'])) {
    delete_transient($this->get_stripe_connect_pending_reference_transient_key((string) $pending['encryption']['public_key_hash']));
}
