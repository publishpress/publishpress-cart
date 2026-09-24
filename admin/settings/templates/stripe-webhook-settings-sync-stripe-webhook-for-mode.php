<?php

if (! defined('ABSPATH')) {
    exit;
}


if ('' === $secret_key) {
    return new WP_Error('missing_secret_key', __('Cannot configure webhooks without a Stripe secret key for the active mode.', 'publishpress-cart'));
}

$webhook_url = $this->get_stripe_webhook_url();
if ($this->is_local_webhook_url($webhook_url)) {
    return new WP_Error('local_webhook_url', __('Stripe cannot reach local-only webhook URLs (like .test/.local). Use a public tunnel URL for webhook testing, or use Stripe CLI forwarding while testing locally.', 'publishpress-cart'));
}
$events = $this->get_expected_stripe_webhook_events();

try {
    $stripe = ppcart_stripe_client($secret_key);
    $option_id_key = 'live' === $mode ? '_ppcart_stripe_live_webhook_id' : '_ppcart_stripe_test_webhook_id';
    $option_secret_key = 'live' === $mode ? '_ppcart_stripe_live_webhook_secret' : '_ppcart_stripe_test_webhook_secret';
    $stored_id = sanitize_text_field((string) get_option($option_id_key, ''));
    $had_secret = '' !== sanitize_text_field((string) ppcart_get_sensitive_option($option_secret_key, ''));

    $endpoint = null;

    if ('' !== $stored_id) {
        try {
            $endpoint = $stripe->webhookEndpoints->retrieve($stored_id, []);
        } catch (\Exception $e) {
            // Leave the stored id and signing secret alone. Wiping them here
            // drops the only copy of whsec_ before create/update has a
            // replacement, and incoming Stripe events then fail signature checks.
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

    if (null !== $endpoint && isset($endpoint->id)) {
        $endpoint = $stripe->webhookEndpoints->update(
            $endpoint->id,
            [
                'enabled_events' => $events,
            ]
        );
    } else {
        $endpoint = $stripe->webhookEndpoints->create(
            [
                'url'            => $webhook_url,
                'enabled_events' => $events,
            ]
        );
    }

    if (isset($endpoint->id)) {
        update_option($option_id_key, sanitize_text_field((string) $endpoint->id));
    }

    // Stripe returns secret only from create(). update() leaves it empty.
    $secret_saved = false;
    if (isset($endpoint->secret) && '' !== $endpoint->secret) {
        ppcart_set_sensitive_option($option_secret_key, sanitize_text_field((string) $endpoint->secret));
        $secret_saved = true;
    }

    return [
        'secret_saved' => $secret_saved,
        'has_secret'   => $secret_saved || $had_secret,
    ];
} catch (\Exception $e) {
    if ($this->is_stripe_webhook_permission_error($e)) {
        return new WP_Error('webhook_permission', __('This connected account token cannot manage webhooks automatically. Please create the webhook manually in Stripe dashboard for the active mode.', 'publishpress-cart'));
    }

    return new WP_Error('webhook_setup_failed', $e->getMessage());
}
