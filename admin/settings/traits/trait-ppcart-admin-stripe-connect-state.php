<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Stripe_Connect_State_Trait
{
    private function get_stripe_connect_site_secret()
    {
        $secret = get_option('_ppcart_stripe_connect_site_secret', '');
        if (is_string($secret) && strlen($secret) >= 43) {
            return $secret;
        }

        $secret = $this->base64url_encode(random_bytes(32));
        update_option('_ppcart_stripe_connect_site_secret', $secret, false);

        return $secret;
    }

    private function get_stripe_mode()
    {
        $mode = sanitize_text_field((string) get_option('_ppcart_stripe_api', 'test'));
        return in_array($mode, [ 'test', 'live' ], true) ? $mode : 'test';
    }

    private function get_stripe_connect_account_option_name($mode)
    {
        return 'live' === $mode ? '_ppcart_stripe_connect_account_id_live' : '_ppcart_stripe_connect_account_id_test';
    }

    private function get_stripe_connect_account_id_for_mode($mode)
    {
        $option_key = $this->get_stripe_connect_account_option_name($mode);
        $account_id = sanitize_text_field((string) get_option($option_key, ''));

        if ('' !== $account_id && 0 !== strpos($account_id, 'acct_')) {
            return '';
        }

        return $account_id;
    }

    public function get_stripe_secret_key_for_mode($mode)
    {
        if (function_exists('ppcart_get_stripe_platform_credentials')) {
            $credentials = ppcart_get_stripe_platform_credentials($mode);
            return sanitize_text_field((string) $credentials['sk']);
        }

        return '';
    }

    private function get_stripe_credentials_status_for_mode($mode)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-credentials-status.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_stripe_connect_settings_url()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-settings-url.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_stripe_connect_server_url()
    {
        $url = defined('PPCART_STRIPE_CONNECT_SERVER_URL') ? (string) PPCART_STRIPE_CONNECT_SERVER_URL : '';
        $url = trim((string) $url);

        if ('' === $url) {
            return '';
        }

        return untrailingslashit(esc_url_raw($url));
    }

    private function get_stripe_connect_pending_transient_key($state)
    {
        return 'ppcart_stripe_connect_pending_' . md5((string) $state);
    }

    private function get_stripe_connect_pending_user_transient_key($user_id, $mode)
    {
        return 'ppcart_stripe_connect_pending_user_' . absint($user_id) . '_' . sanitize_key($mode);
    }

    private function get_stripe_connect_pending_reference_transient_key($reference)
    {
        return 'ppcart_stripe_connect_pending_ref_' . md5((string) $reference);
    }

    private function store_pending_stripe_connect_state($state, $mode, $return_url, $website_url, $key_pair)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-state-store-pending-stripe-connect-state.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_pending_stripe_connect_state($mode)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-state-get-pending-stripe-connect-state.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_pending_stripe_connect_state_by_state($state)
    {
        if ('' === (string) $state) {
            return [];
        }

        $pending = get_transient($this->get_stripe_connect_pending_transient_key($state));

        return is_array($pending) ? $pending : [];
    }

    private function get_pending_stripe_connect_state_by_reference($reference)
    {
        if ('' === (string) $reference) {
            return [];
        }

        $state = get_transient($this->get_stripe_connect_pending_reference_transient_key($reference));

        return $this->get_pending_stripe_connect_state_by_state((string) $state);
    }

    private function stripe_connect_urls_match($url_a, $url_b)
    {
        return hash_equals(
            $this->normalize_stripe_connect_url_for_compare($url_a),
            $this->normalize_stripe_connect_url_for_compare($url_b)
        );
    }

    private function normalize_stripe_connect_url_for_compare($url)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-normalize-url.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function log_stripe_connect_debug($event, $context = [], $level = 1)
    {
        include __DIR__ . '/templates/stripe-connect-debug-log.php';
    }

    private function delete_pending_stripe_connect_state($pending)
    {
        include __DIR__ . '/templates/stripe-connect-delete-pending-state.php';
    }
}
