<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resolves the webhook state for one Stripe mode.
 *
 * Returns: state (active|missing|incomplete|blocked|local|unknown),
 * events (int), note (string) and fix_url (string).
 */

$state = [
    'state'   => 'unknown',
    'events'  => count($this->get_expected_stripe_webhook_events()),
    'note'    => '',
    'fix_url' => '',
];

$secret_key = $this->connect_settings->get_stripe_secret_key_for_mode($mode);
if ('' === $secret_key) {
    $state['note'] = __('Add a Stripe secret key for this mode first.', 'publishpress-cart');
    return $state;
}

$option_id_key = 'live' === $mode ? '_ppcart_stripe_live_webhook_id' : '_ppcart_stripe_test_webhook_id';
$option_secret_key = 'live' === $mode ? '_ppcart_stripe_live_webhook_secret' : '_ppcart_stripe_test_webhook_secret';
$stored_id = sanitize_text_field((string) get_option($option_id_key, ''));
$stored_secret = sanitize_text_field((string) ppcart_get_sensitive_option($option_secret_key, ''));
$webhook_url = $this->get_stripe_webhook_url();
$expected_events = $this->get_expected_stripe_webhook_events();

if ($this->is_local_webhook_url($webhook_url)) {
    $state['state'] = 'local';
    /* translators: %s: local webhook URL. */
    $state['note'] = sprintf(__('Stripe cannot reach a local address. Forward events with Stripe CLI to %s', 'publishpress-cart'), $webhook_url);
    return $state;
}

$key_source = sanitize_text_field((string) get_option('live' === $mode ? '_ppcart_stripe_live_key_source' : '_ppcart_stripe_test_key_source', ''));
if ('oauth_access_token' !== $key_source) {
    $state['fix_url'] = wp_nonce_url(
        add_query_arg(
            [
                'page'                           => PPCart_Admin_Screens::PAGE_SETTINGS,
                'ppcart_stripe_connect_setup_webhook' => '1',
                'ppcart_stripe_mode'                 => $mode,
                'ppcart_payment_subtab'              => 'method:stripe',
            ],
            admin_url('admin.php')
        ),
        'ppcart_stripe_connect_setup_webhook',
        'ppcart_stripe_connect_nonce'
    );
}

try {
    $stripe = ppcart_stripe_client($secret_key);
    $endpoint = null;

    if ('' !== $stored_id) {
        try {
            $endpoint = $stripe->webhookEndpoints->retrieve($stored_id, []);
        } catch (\Exception $e) {
            $endpoint = null;
        }
    }

    if (null === $endpoint) {
        $existing = $stripe->webhookEndpoints->all([ 'limit' => 100 ]);
        if (isset($existing->data) && is_array($existing->data)) {
            foreach ($existing->data as $candidate) {
                if (isset($candidate->url) && $candidate->url === $webhook_url) {
                    $endpoint = $candidate;
                    break;
                }
            }
        }
    }

    if (null === $endpoint || ! isset($endpoint->id) || '' === $stored_secret) {
        $state['state'] = 'missing';
        return $state;
    }

    $enabled_events = isset($endpoint->enabled_events) && is_array($endpoint->enabled_events) ? $endpoint->enabled_events : [];
    if (array_diff($expected_events, $enabled_events)) {
        $state['state'] = 'incomplete';
        $state['note'] = __('Some events are missing, so parts of your store will not update.', 'publishpress-cart');
        return $state;
    }

    $state['state'] = 'active';
    return $state;
} catch (\Exception $e) {
    if ($this->is_stripe_webhook_permission_error($e)) {
        // This token cannot read endpoints. Incoming events only need the
        // signing secret, so a stored whsec_ is enough. That includes the
        // manual paste path, which never saves a webhook id.
        if ('' !== $stored_secret) {
            $state['state'] = 'active';
            $state['note'] = __('This account does not let us re-check the endpoint, so no live status is shown.', 'publishpress-cart');
            return $state;
        }

        $state['state'] = 'blocked';
        return $state;
    }

    /* translators: %s: error message from Stripe. */
    $state['note'] = sprintf(__('Webhook check failed: %s', 'publishpress-cart'), $e->getMessage());
    return $state;
}
