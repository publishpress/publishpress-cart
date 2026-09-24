<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Account_Passwords_Trait
{
    public function do_password_lost()
    {

        if ($this->maybe_use_default_login_authentication()) {
            return;
        }

        $request_method = isset($_SERVER['REQUEST_METHOD'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))
            : '';
        if ('POST' === $request_method) {
            $nonce = '';
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified with ppcart_verify_nonce() below.
            if (isset($_POST['ppcart_lost_password_nonce'])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified with ppcart_verify_nonce() below.
                $nonce = sanitize_text_field(wp_unslash($_POST['ppcart_lost_password_nonce']));
            }
            if (false === ppcart_verify_nonce($nonce, 'ppcart_lost_password')) {
                $redirect_url = add_query_arg(
                    [
                        'action' => 'lostpassword',
                        'errors' => 'lost_password_invalid_nonce',
                    ],
                    $this->my_account_url
                );
                wp_safe_redirect($redirect_url);
                exit;
            }

            $errors = retrieve_password();
            if (is_wp_error($errors)) {
                // Errors found
                $args = ['action' => 'lostpassword','errors' => join(',', $errors->get_error_codes())];
                $redirect_url = add_query_arg($args, $this->my_account_url);
            } else {
                // Email sent
                $args = ['action' => 'lostpassword','checkemail' => 'confirm'];
                $redirect_url = add_query_arg($args, $this->my_account_url);
            }

            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    public function redirect_to_custom_password_reset()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/account-passwords-redirect-to-custom-password-reset.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function do_password_reset()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/account-passwords-do-password-reset.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function custom_password_reset_email($message, $key, $user_login, $user_data)
    {
        // Only modify the email if the custom account URL is available
        if (! empty($this->my_account_url)) {
            // Build custom reset URL
            $reset_url = $this->my_account_url . '?action=reset&key=' . rawurlencode($key) . '&login=' . rawurlencode($user_login);

            // Construct custom message
            $msg  = "Hi {$user_login},\n\n";
            $msg .= "You requested a password reset for your account.\n\n";
            $msg .= "To reset your password, click the following link:\n\n";
            $msg .= $reset_url . "\n\n";
            $msg .= "If you did not request this, you can safely ignore this email.\n";

            return $msg;
        }
        return $message;
    }
}
