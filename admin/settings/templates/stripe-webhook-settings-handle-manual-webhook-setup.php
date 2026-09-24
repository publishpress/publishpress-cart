<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! current_user_can('manage_options')) {
    wp_die(esc_html__('You are not allowed to configure Stripe webhooks.', 'publishpress-cart'));
}

ppcart_check_admin_referer('ppcart_stripe_webhook_manual_setup', 'ppcart_stripe_webhook_nonce');

// phpcs:disable WordPress.Security.NonceVerification.Missing -- Request fields are read only after ppcart_check_admin_referer() validates this form action.
$requested_mode = isset($_POST['ppcart_stripe_mode']) ? sanitize_text_field((string) wp_unslash($_POST['ppcart_stripe_mode'])) : '';
$stripe_mode = in_array($requested_mode, [ 'test', 'live' ], true) ? $requested_mode : $this->connect_settings->get_stripe_mode();

// One-time account-owned key. Never stored: it lives only inside this request.
$account_key = isset($_POST['ppcart_stripe_webhook_account_key'])
    ? trim(sanitize_text_field((string) wp_unslash($_POST['ppcart_stripe_webhook_account_key'])))
    : '';

$signing_secret = isset($_POST['ppcart_stripe_webhook_signing_secret'])
    ? trim(sanitize_text_field((string) wp_unslash($_POST['ppcart_stripe_webhook_signing_secret'])))
    : '';
// phpcs:enable WordPress.Security.NonceVerification.Missing

$notice = null;

if ('' !== $account_key) {
    if (! $this->is_stripe_account_key($account_key, $stripe_mode)) {
        $notice = [
            'type'    => 'error',
            'message' => __('That key does not look like a Stripe key for the selected mode. Copy a restricted key that starts with rk_, or a secret key that starts with sk_.', 'publishpress-cart'),
        ];
    } else {
        $result = $this->sync_stripe_webhook_for_mode($stripe_mode, $account_key);

        if (is_wp_error($result)) {
            $notice = [ 'type' => 'error', 'message' => $result->get_error_message() ];
        } elseif (! empty($result['secret_saved'])) {
            $notice = [
                'type'    => 'success',
                'message' => __('Webhook is active and the signing secret is saved. The key you pasted was not stored, so you can delete it in Stripe now.', 'publishpress-cart'),
            ];
        } elseif (! empty($result['has_secret'])) {
            $notice = [
                'type'    => 'success',
                'message' => __('Webhook events were updated. The key you pasted was not stored, so you can delete it in Stripe now.', 'publishpress-cart'),
            ];
        } else {
            // update() ran on an existing endpoint. Stripe does not return
            // whsec_ there, so the owner must paste it from the Dashboard.
            $notice = [
                'type'    => 'warning',
                'message' => __('That webhook endpoint already existed, so Stripe did not return a signing secret. Open the endpoint in your Stripe Dashboard, copy the signing secret, and paste it below.', 'publishpress-cart'),
            ];
            set_transient('ppcart_stripe_webhook_need_signing_secret', $stripe_mode, MINUTE_IN_SECONDS);
        }
    }

    // Drop the one-time key from memory as early as possible.
    $account_key = '';
    unset($_POST['ppcart_stripe_webhook_account_key']);
} elseif ('' !== $signing_secret) {
    if (0 !== strpos($signing_secret, 'whsec_')) {
        $notice = [
            'type'    => 'error',
            'message' => __('A Stripe signing secret starts with whsec_. Copy it again from the webhook endpoint in your Stripe Dashboard.', 'publishpress-cart'),
        ];
    } else {
        $option_secret_key = 'live' === $stripe_mode ? '_ppcart_stripe_live_webhook_secret' : '_ppcart_stripe_test_webhook_secret';
        ppcart_set_sensitive_option($option_secret_key, $signing_secret);

        $notice = [
            'type'    => 'success',
            'message' => __('Signing secret saved. Send a test event from Stripe to confirm that your site accepts it.', 'publishpress-cart'),
        ];
    }
} else {
    $notice = [
        'type'    => 'error',
        'message' => __('Paste a Stripe key or a signing secret before you save.', 'publishpress-cart'),
    ];
}

set_transient('ppcart_stripe_connect_admin_notice', $notice, MINUTE_IN_SECONDS);

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
