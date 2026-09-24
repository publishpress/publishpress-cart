<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is only a route check; ppcart_check_admin_referer() validates before mutation.
$is_setup = isset($_GET['ppcart_stripe_connect_setup_webhook']) && '1' === sanitize_text_field((string) wp_unslash($_GET['ppcart_stripe_connect_setup_webhook']));
if (! $is_setup) {
    return;
}

if (! current_user_can('manage_options')) {
    return;
}

ppcart_check_admin_referer('ppcart_stripe_connect_setup_webhook', 'ppcart_stripe_connect_nonce');

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read occurs after ppcart_check_admin_referer() validates the setup action.
$requested_mode = isset($_GET['ppcart_stripe_mode']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_stripe_mode'])) : '';
$stripe_mode = in_array($requested_mode, [ 'test', 'live' ], true) ? $requested_mode : $this->connect_settings->get_stripe_mode();
$key_source = sanitize_text_field((string) get_option('live' === $stripe_mode ? '_ppcart_stripe_live_key_source' : '_ppcart_stripe_test_key_source', ''));

if ('oauth_access_token' === $key_source) {
    set_transient(
        'ppcart_stripe_connect_admin_notice',
        [
            'type'    => 'error',
            'message' => __('This connected account cannot update webhooks automatically. Paste a restricted key with Webhook endpoints: Write, or create the endpoint in Stripe yourself.', 'publishpress-cart'),
        ],
        MINUTE_IN_SECONDS
    );
} else {
    $secret_key = $this->connect_settings->get_stripe_secret_key_for_mode($stripe_mode);
    $webhook_sync_result = $this->sync_stripe_webhook_for_mode($stripe_mode, $secret_key);

    if (is_wp_error($webhook_sync_result)) {
        set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => $webhook_sync_result->get_error_message() ], MINUTE_IN_SECONDS);
    } elseif (! empty($webhook_sync_result['has_secret'])) {
        set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'success', 'message' => __('Stripe webhook is configured for the selected mode.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
    } else {
        set_transient(
            'ppcart_stripe_connect_admin_notice',
            [
                'type'    => 'warning',
                'message' => __('That webhook endpoint already existed, so Stripe did not return a signing secret. Open the endpoint in your Stripe Dashboard, copy the signing secret, and paste it below.', 'publishpress-cart'),
            ],
            MINUTE_IN_SECONDS
        );
        set_transient('ppcart_stripe_webhook_need_signing_secret', $stripe_mode, MINUTE_IN_SECONDS);
    }
}

// Built here because the connect settings URL helper is private to its own class.
$redirect_url = add_query_arg(
    [
        'page'              => PPCart_Admin_Screens::PAGE_SETTINGS,
        'tab'               => 'payment',
        'ppcart_payment_subtab' => 'method:stripe',
    ],
    admin_url('admin.php')
);

wp_safe_redirect($redirect_url);
exit;
