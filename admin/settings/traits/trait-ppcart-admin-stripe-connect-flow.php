<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Stripe_Connect_Flow_Trait
{
    private function get_stripe_connect_authorize_url($mode = null)
    {
        $mode = in_array($mode, [ 'test', 'live' ], true) ? $mode : $this->get_stripe_mode();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation state.
        $payment_subtab = isset($_GET['ppcart_payment_subtab']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_payment_subtab'])) : '';
        $is_valid_payment_subtab = '' !== $payment_subtab && preg_match('/^(enable|method:[a-z0-9_\-]+)$/i', $payment_subtab);

        $args = [
            'page'                    => PPCart_Admin_Screens::PAGE_SETTINGS,
            'ppcart_stripe_connect_start' => '1',
            'ppcart_stripe_mode'          => $mode,
        ];

        if ($is_valid_payment_subtab) {
            $args['ppcart_payment_subtab'] = $payment_subtab;
        }

        return wp_nonce_url(
            add_query_arg($args, admin_url('admin.php')),
            'ppcart_stripe_connect_start',
            'ppcart_stripe_connect_nonce'
        );
    }

    public function maybe_handle_stripe_connect_start()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-flow-maybe-handle-stripe-connect-start.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function preregister_stripe_connect_session($connect_server_url, $state, $mode, $return_url, $website_url, $key_pair)
    {
        $issued_at = time();
        $nonce = wp_generate_uuid4();
        $public_key_hash = isset($key_pair['public_key_hash']) ? sanitize_text_field((string) $key_pair['public_key_hash']) : hash('sha256', (string) $key_pair['public_key']);
        $payload = [
            'state'                      => sanitize_text_field((string) $state),
            'mode'                       => 'live' === $mode ? 'live' : 'test',
            'return_url'                 => esc_url_raw((string) $return_url),
            'website_url'                => esc_url_raw((string) $website_url),
            'encryption_algorithm'       => sanitize_key((string) $key_pair['algorithm']),
            'encryption_public_key'      => sanitize_textarea_field((string) $key_pair['public_key']),
            'encryption_public_key_hash' => $public_key_hash,
            'issued_at'                  => $issued_at,
            'nonce'                      => $nonce,
        ];

        $site_credential = $this->encrypt_stripe_connect_site_credential($connect_server_url, $website_url);
        if (is_wp_error($site_credential)) {
            return $site_credential;
        }

        $payload['site_credential'] = $site_credential;
        $payload['signature'] = hash_hmac('sha256', $this->build_stripe_connect_preregistration_canonical_string($payload), $this->get_stripe_connect_site_secret());

        $response = wp_remote_post(
            add_query_arg('ppcsc_action', 'connect_preregister', trailingslashit($connect_server_url)),
            [
                'timeout' => 3,
                'body'    => $payload,
            ]
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'stripe_connect_preregistration_http_error',
                sprintf(
                    /* translators: %s: HTTP error message. */
                    __('The Stripe Connect server could not preregister this session. Error: %s', 'publishpress-cart'),
                    $response->get_error_message()
                )
            );
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (200 !== $status_code || empty($body['success'])) {
            $message = is_array($body) && ! empty($body['data']['message'])
                ? sanitize_text_field((string) $body['data']['message'])
                : __('The Stripe Connect server rejected this preregistration request.', 'publishpress-cart');

            return new WP_Error('stripe_connect_preregistration_rejected', $message);
        }

        return true;
    }

    private function encrypt_stripe_connect_site_credential($connect_server_url, $website_url)
    {
        $public_key = $this->get_stripe_connect_preregistration_public_key($connect_server_url);
        if (is_wp_error($public_key)) {
            return $public_key;
        }

        $plaintext = wp_json_encode([
            'website_url'         => esc_url_raw((string) $website_url),
            'site_connect_secret' => $this->get_stripe_connect_site_secret(),
        ]);

        if (false === $plaintext) {
            return new WP_Error('stripe_connect_site_credential_json_error', __('Unable to prepare the Stripe Connect site credential.', 'publishpress-cart'));
        }

        $ciphertext = '';
        if (! openssl_public_encrypt($plaintext, $ciphertext, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            delete_transient('ppcart_stripe_connect_prereg_public_key_' . md5($connect_server_url));
            return new WP_Error('stripe_connect_site_credential_encryption_error', __('Unable to encrypt the Stripe Connect site credential.', 'publishpress-cart'));
        }

        return base64_encode($ciphertext);
    }

    private function get_stripe_connect_preregistration_public_key($connect_server_url)
    {
        $cache_key = 'ppcart_stripe_connect_prereg_public_key_' . md5($connect_server_url);
        $public_key = get_transient($cache_key);
        if (is_string($public_key) && false !== openssl_pkey_get_public($public_key)) {
            return $public_key;
        }

        $public_key_url = add_query_arg('ppcsc_action', 'connect_public_key', trailingslashit($connect_server_url));
        $request_args = [ 'timeout' => 3 ];

        if (function_exists('vip_safe_wp_remote_get')) {
            $response = vip_safe_wp_remote_get($public_key_url, '', 3, 1, 20, $request_args);
        } else {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- VIP helper is only available on VIP; keep standard WordPress fallback for other installs.
            $response = wp_remote_get($public_key_url, $request_args);
        }

        if (is_wp_error($response)) {
            return new WP_Error(
                'stripe_connect_public_key_http_error',
                sprintf(
                    /* translators: %s: HTTP error message. */
                    __('Unable to fetch the Stripe Connect preregistration public key. Error: %s', 'publishpress-cart'),
                    $response->get_error_message()
                )
            );
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $public_key = is_array($body) && ! empty($body['data']['public_key']) ? (string) $body['data']['public_key'] : '';

        if (200 !== (int) wp_remote_retrieve_response_code($response) || empty($body['success']) || false === openssl_pkey_get_public($public_key)) {
            return new WP_Error('stripe_connect_public_key_invalid', __('The Stripe Connect preregistration public key is unavailable or invalid.', 'publishpress-cart'));
        }

        set_transient($cache_key, $public_key, DAY_IN_SECONDS);

        return $public_key;
    }

    private function build_stripe_connect_preregistration_canonical_string($payload)
    {
        return implode(
            "\n",
            [
                'connect_preregister',
                (string) $payload['state'],
                (string) $payload['mode'],
                $this->normalize_stripe_connect_url_for_compare($payload['return_url']),
                $this->normalize_stripe_connect_url_for_compare($payload['website_url']),
                (string) $payload['encryption_algorithm'],
                (string) $payload['encryption_public_key_hash'],
                (string) $payload['issued_at'],
                (string) $payload['nonce'],
            ]
        );
    }

    private function build_stripe_connect_credentials_canonical_string($payload)
    {
        return implode(
            "\n",
            [
                'get_credentials',
                (string) $payload['state'],
                (string) $payload['mode'],
                $this->normalize_stripe_connect_url_for_compare($payload['return_url']),
                $this->normalize_stripe_connect_url_for_compare($payload['website_url']),
                (string) $payload['issued_at'],
                (string) $payload['nonce'],
            ]
        );
    }

    public function maybe_handle_stripe_connect_proxy_return()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-maybe-handle-proxy-return.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function maybe_handle_stripe_connect_disconnect()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is only a route check; ppcart_check_admin_referer() validates before mutation.
        $is_disconnect = isset($_GET['ppcart_stripe_connect_disconnect']) && '1' === sanitize_text_field((string) wp_unslash($_GET['ppcart_stripe_connect_disconnect']));
        if (! $is_disconnect) {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        ppcart_check_admin_referer('ppcart_stripe_connect_disconnect', 'ppcart_stripe_connect_nonce');

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read occurs after ppcart_check_admin_referer() validates the disconnect action.
        $requested_mode = isset($_GET['ppcart_stripe_mode']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_stripe_mode'])) : '';
        $mode = in_array($requested_mode, [ 'test', 'live' ], true) ? $requested_mode : $this->get_stripe_mode();
        delete_option($this->get_stripe_connect_account_option_name($mode));
        delete_option('live' === $mode ? '_ppcart_stripe_connect_account_country_live' : '_ppcart_stripe_connect_account_country_test');
        delete_option('live' === $mode ? '_ppcart_stripe_live_pk' : '_ppcart_stripe_test_pk');
        delete_option('live' === $mode ? '_ppcart_stripe_live_sk' : '_ppcart_stripe_test_sk');
        delete_option('live' === $mode ? '_ppcart_stripe_live_key_source' : '_ppcart_stripe_test_key_source');

        set_transient('ppcart_stripe_connect_admin_notice', [ 'type' => 'success', 'message' => __('Stripe account disconnected for the selected mode.', 'publishpress-cart') ], MINUTE_IN_SECONDS);
        wp_safe_redirect($this->get_stripe_connect_settings_url());
        exit;
    }
}
