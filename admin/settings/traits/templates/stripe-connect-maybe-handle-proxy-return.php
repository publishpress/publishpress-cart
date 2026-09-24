<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only callback flag validated before use.
if (! isset($_GET['ppcart_stripe_connected'])) {
    return;
}

if (! current_user_can('manage_options')) {
    return;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- OAuth callback query args are sanitized and matched to server-side pending state below.
$connected = sanitize_text_field((string) wp_unslash($_GET['ppcart_stripe_connected']));
if ('1' !== $connected) {
    return;
}

$redirect_url = $this->get_stripe_connect_settings_url();
$account_id = isset($_GET['ppcart_account_id']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_account_id'])) : '';
$stripe_mode = isset($_GET['mode']) ? sanitize_text_field((string) wp_unslash($_GET['mode'])) : $this->get_stripe_mode();
$stripe_mode = in_array($stripe_mode, [ 'test', 'live' ], true) ? $stripe_mode : $this->get_stripe_mode();
$connect_reference = isset($_GET['ppcart_connect_ref']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_connect_ref'])) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$pending = $this->get_pending_stripe_connect_state_by_reference($connect_reference);

if (empty($pending) || (string) $pending['mode'] !== (string) $stripe_mode || (int) $pending['user_id'] !== get_current_user_id()) {
    $pending = [];
}

if ('' === $account_id || 0 !== strpos($account_id, 'acct_')) {
    $error_message = __('Connect server did not return a valid Stripe account ID.', 'publishpress-cart');
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Callback error message is sanitized before display.
    if (isset($_GET['ppcart_connect_error'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Callback error message is sanitized before display.
        $error_message = sanitize_text_field((string) wp_unslash($_GET['ppcart_connect_error']));
    }
    if (! empty($pending)) {
        $this->delete_pending_stripe_connect_state($pending);
    }
    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => $error_message ], MINUTE_IN_SECONDS);
    wp_safe_redirect($redirect_url);
    exit;
}

$connect_server_url = $this->get_stripe_connect_server_url();
if ('' === $connect_server_url || empty($pending)) {
    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => __('This Stripe Connect session is missing or expired. This can happen after a failed, abandoned, or restarted connection attempt. Reload this settings page and start the connection again.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
    wp_safe_redirect($redirect_url);
    exit;
}

$state = sanitize_text_field((string) $pending['state']);
$return_url = esc_url_raw((string) $pending['return_url']);
$website_url = esc_url_raw((string) $pending['website_url']);
$issued_at = time();
$nonce = wp_generate_uuid4();
$credentials_request_payload = [
    'state'       => $state,
    'mode'        => $stripe_mode,
    'return_url'  => $return_url,
    'website_url' => $website_url,
    'issued_at'   => $issued_at,
    'nonce'       => $nonce,
];
$credentials_request_payload['signature'] = hash_hmac(
    'sha256',
    $this->build_stripe_connect_credentials_canonical_string($credentials_request_payload),
    $this->get_stripe_connect_site_secret()
);
$credentials_request_args = [
    // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Stripe Connect credential exchange can take longer than a normal local request.
    'timeout'   => 20,
    'sslverify' => true,
    'body'      => array_merge([ 'ppcsc_action' => 'get_credentials' ], $credentials_request_payload),
];

$credentials_request_args = apply_filters('ppcart_stripe_connect_credentials_http_args', $credentials_request_args, $connect_server_url, $stripe_mode);

$connect_server_host = wp_parse_url($connect_server_url, PHP_URL_HOST);
$connect_server_host = is_string($connect_server_host) ? strtolower($connect_server_host) : '';
$is_local_connect_server = in_array($connect_server_host, [ 'localhost', '127.0.0.1', '::1' ], true)
    || 1 === preg_match('/\.(test|local|localhost|invalid)$/i', $connect_server_host);
$allows_insecure_local_credentials_request = defined('PPCART_STRIPE_CONNECT_ALLOW_INSECURE_LOCAL_CREDENTIALS')
    && PPCART_STRIPE_CONNECT_ALLOW_INSECURE_LOCAL_CREDENTIALS
    && defined('WP_DEBUG')
    && WP_DEBUG
    && 'live' !== $stripe_mode
    && $is_local_connect_server;

if (! $allows_insecure_local_credentials_request) {
    $credentials_request_args['sslverify'] = true;
}

$credentials_response = wp_remote_post($connect_server_url . '/', $credentials_request_args);

if (is_wp_error($credentials_response)) {
    $error_message = $credentials_response->get_error_message();
    if (false !== stripos((string) $error_message, 'cURL error 60')) {
        $error_message = __('SSL verification failed when retrieving keys from the connect server. Use a trusted certificate, or explicitly enable insecure local credential pickup for non-live local development.', 'publishpress-cart');
    }

    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => $error_message ], MINUTE_IN_SECONDS);
    $this->delete_pending_stripe_connect_state($pending);
    wp_safe_redirect($redirect_url);
    exit;
}

$credentials_payload = json_decode(wp_remote_retrieve_body($credentials_response), true);
$credentials_data = (is_array($credentials_payload) && ! empty($credentials_payload['success']) && ! empty($credentials_payload['data']) && is_array($credentials_payload['data']))
    ? $credentials_payload['data']
    : [];

$encrypted_credentials = ! empty($credentials_data['encrypted_credentials']) && is_array($credentials_data['encrypted_credentials'])
    ? $credentials_data['encrypted_credentials']
    : [];
$decrypted_credentials = $this->decrypt_stripe_connect_credentials($encrypted_credentials, $pending);
$publishable_key = ! empty($decrypted_credentials['publishable_key']) ? sanitize_text_field((string) $decrypted_credentials['publishable_key']) : '';
$secret_key = ! empty($decrypted_credentials['secret_key']) ? sanitize_text_field((string) $decrypted_credentials['secret_key']) : '';
$credentials_account_id = ! empty($decrypted_credentials['account_id']) ? sanitize_text_field((string) $decrypted_credentials['account_id']) : '';

if ('' === $publishable_key || '' === $secret_key || '' === $credentials_account_id || $credentials_account_id !== $account_id) {
    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => __('Unable to retrieve Stripe API keys from the Connect server for this session. Reload this settings page and start the connection again.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
    $this->delete_pending_stripe_connect_state($pending);
    wp_safe_redirect($redirect_url);
    exit;
}

update_option('live' === $stripe_mode ? '_ppcart_stripe_live_pk' : '_ppcart_stripe_test_pk', $publishable_key);
update_option('live' === $stripe_mode ? '_ppcart_stripe_live_sk' : '_ppcart_stripe_test_sk', $secret_key);
update_option('live' === $stripe_mode ? '_ppcart_stripe_live_key_source' : '_ppcart_stripe_test_key_source', 'oauth_access_token');

update_option($this->get_stripe_connect_account_option_name($stripe_mode), $account_id);

set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'success', 'message' => __('Stripe account connected successfully.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
$this->delete_pending_stripe_connect_state($pending);

wp_safe_redirect($redirect_url);
exit;
