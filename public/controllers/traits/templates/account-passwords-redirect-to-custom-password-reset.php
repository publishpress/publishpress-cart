<?php

if (! defined('ABSPATH')) {
    exit;
}


if ($this->maybe_use_default_login_authentication()) {
    return;
}

$redirect_url = $this->my_account_url;
$redirect_url = add_query_arg('action', 'lostpassword', $redirect_url);

$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$key_request = ppcart_filter_input_request('key', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$login_request = ppcart_filter_input_request('login', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if ('GET' === $request_method) {
    if (is_string($key_request) && '' !== $key_request && is_string($login_request) && '' !== $login_request) {
        // Verify key / login combo
        $user = check_password_reset_key(sanitize_text_field($key_request), sanitize_text_field($login_request));
        if (! $user || is_wp_error($user)) {
            if ($user && $user->get_error_code() === 'expired_key') {
                wp_safe_redirect(add_query_arg('errors', 'expiredkey', $redirect_url));
                exit;
            } else {
                wp_safe_redirect(add_query_arg('errors', 'invalidkey', $redirect_url));
                exit;
            }
        }

        $redirect_url = add_query_arg('login', sanitize_text_field($login_request), $redirect_url);
        $redirect_url = add_query_arg('key', sanitize_text_field($key_request), $redirect_url);
    }
}

wp_safe_redirect($redirect_url);
exit;
