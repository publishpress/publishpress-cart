<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is only a route check; ppcart_check_admin_referer() validates before mutation.
$is_start = isset($_GET['ppcart_stripe_connect_start']) && '1' === sanitize_text_field((string) wp_unslash($_GET['ppcart_stripe_connect_start']));
if (! $is_start) {
    return;
}

if (! current_user_can('manage_options')) {
    return;
}

ppcart_check_admin_referer('ppcart_stripe_connect_start', 'ppcart_stripe_connect_nonce');

$redirect_url = $this->get_stripe_connect_settings_url();
$connect_server_url = $this->get_stripe_connect_server_url();
if ('' === $connect_server_url) {
    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => __('Stripe Connect server URL is not configured.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
    wp_safe_redirect($redirect_url);
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read occurs after ppcart_check_admin_referer() validates the connect-start action.
$requested_mode = isset($_GET['ppcart_stripe_mode']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_stripe_mode'])) : '';
$stripe_mode = in_array($requested_mode, [ 'test', 'live' ], true) ? $requested_mode : $this->get_stripe_mode();

$return_args = [
    'page'                => PPCart_Admin_Screens::PAGE_SETTINGS,
    'ppcart_stripe_connected' => '1',
];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read occurs after ppcart_check_admin_referer() validates the connect-start action.
$payment_subtab = isset($_GET['ppcart_payment_subtab']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_payment_subtab'])) : '';
if ('' !== $payment_subtab && preg_match('/^(enable|method:[a-z0-9_\-]+)$/i', $payment_subtab)) {
    $return_args['ppcart_payment_subtab'] = $payment_subtab;
}

$return_url = add_query_arg($return_args, admin_url('admin.php'));
$website_url = get_site_url();

if ('live' === $stripe_mode && ('https' !== wp_parse_url($return_url, PHP_URL_SCHEME) || 'https' !== wp_parse_url($website_url, PHP_URL_SCHEME))) {
    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => __('Stripe Connect live mode requires HTTPS for the site and return URL.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
    wp_safe_redirect($redirect_url);
    exit;
}

$state = wp_generate_uuid4();
$key_pair = $this->generate_stripe_connect_encryption_key_pair();
if (empty($key_pair)) {
    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => __('Unable to generate an encryption key for Stripe Connect.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
    wp_safe_redirect($redirect_url);
    exit;
}

$this->store_pending_stripe_connect_state($state, $stripe_mode, $return_url, $website_url, $key_pair);

$preregistration = $this->preregister_stripe_connect_session($connect_server_url, $state, $stripe_mode, $return_url, $website_url, $key_pair);
if (is_wp_error($preregistration)) {
    $this->delete_pending_stripe_connect_state([
        'state'   => $state,
        'mode'    => $stripe_mode,
        'user_id' => get_current_user_id(),
        'encryption' => [
            'public_key_hash' => $key_pair['public_key_hash'],
        ],
    ]);
    set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'error', 'message' => $preregistration->get_error_message() ], MINUTE_IN_SECONDS);
    wp_safe_redirect($redirect_url);
    exit;
}

$connect_init_url = add_query_arg(
    [
        'ppcsc_action'               => 'connect_init',
        'mode'                       => $stripe_mode,
        'return_url'                 => $return_url,
        'website_url'                => $website_url,
        'state'                      => $state,
        'encryption_algorithm'       => $key_pair['algorithm'],
        'encryption_public_key'      => $key_pair['public_key'],
        'encryption_public_key_hash' => $key_pair['public_key_hash'],
    ],
    $connect_server_url . '/'
);

// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe Connect starts on the configured PublishPress Connect server, which is outside this site's host.
wp_redirect($connect_init_url);
exit;
