<?php

if (! defined('ABSPATH')) {
    exit;
}


if ($this->maybe_use_default_login_authentication()) {
    return;
}

$redirect_url = $this->my_account_url;
$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$rp_key = ppcart_filter_input_request('rp_key', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$rp_login = ppcart_filter_input_request('rp_login', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Password reset is authorized by the reset key/login pair; passwords must remain raw for reset_password().
$pass1 = isset($_POST['pass1']) ? wp_unslash($_POST['pass1']) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Password reset is authorized by the reset key/login pair; passwords must remain raw for reset_password().
$pass2 = isset($_POST['pass2']) ? wp_unslash($_POST['pass2']) : '';
$rp_key = is_string($rp_key) ? sanitize_text_field($rp_key) : '';
$rp_login = is_string($rp_login) ? sanitize_text_field($rp_login) : '';
$pass1 = is_string($pass1) ? $pass1 : '';
$pass2 = is_string($pass2) ? $pass2 : '';

if ('POST' === $request_method) {
    $user = check_password_reset_key($rp_key, $rp_login);

    if (! $user || is_wp_error($user)) {
        if ($user && $user->get_error_code() === 'expired_key') {
            $redirect_url = add_query_arg('login', 'expiredkey', $redirect_url);
        } else {
            $redirect_url = add_query_arg('login', 'invalidkey', $redirect_url);
        }
        wp_safe_redirect($redirect_url);
        exit;
    }

    if ('' !== $pass1) {
        if ($pass1 !== $pass2) {
            // Passwords don't match
            $redirect_url = add_query_arg('action', 'lostpassword', $redirect_url);

            $redirect_url = add_query_arg('key', $rp_key, $redirect_url);
            $redirect_url = add_query_arg('login', $rp_login, $redirect_url);
            $redirect_url = add_query_arg('error', 'password_reset_mismatch', $redirect_url);

            wp_safe_redirect($redirect_url);
            exit;
        }

        if ('' === $pass1) {
            // Password is empty
            $redirect_url = add_query_arg('action', 'lostpassword', $redirect_url);

            $redirect_url = add_query_arg('key', $rp_key, $redirect_url);
            $redirect_url = add_query_arg('login', $rp_login, $redirect_url);
            $redirect_url = add_query_arg('error', 'password_reset_empty', $redirect_url);

            wp_safe_redirect($redirect_url);
            exit;
        }

        // Weak Password
        if (strlen($pass1) < 12 || ! preg_match('/[A-Z]/', $pass1) || ! preg_match('/[a-z]/', $pass1) || ! preg_match('/[0-9]/', $pass1) || ! preg_match('/[\W]/', $pass1)) {
            $redirect_url = add_query_arg('action', 'lostpassword', $redirect_url);
            $redirect_url = add_query_arg('key', $rp_key, $redirect_url);
            $redirect_url = add_query_arg('login', $rp_login, $redirect_url);
            $redirect_url = add_query_arg('error', 'weak_password', $redirect_url);

            wp_safe_redirect($redirect_url);
            exit;
        }

        // Parameter checks OK, reset password
        reset_password($user, $pass1);
        wp_safe_redirect(add_query_arg('password', 'changed', $redirect_url));
        exit;
    } else {
        echo "Invalid request.";
    }

    exit;
}
