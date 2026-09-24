<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe;

// Render-time safety net for postmeta-based page builders.
do_action('ppcart_enqueue_frontend_assets');

// Parse shortcode attributes
$defaults = [ 'show_title' => false, 'hide_login' => false, 'tab' => '' ];
$attr = shortcode_atts($defaults, $attr);
$show_title = $attr['show_title'];
$account_tab = $attr['tab'];
$messages = ppcart_translate_js('ppcart-public.js');
$ppcart_order_request = ppcart_filter_input_request('ppcart-order', FILTER_VALIDATE_INT);
$ppcart_plan_request = ppcart_filter_input_request('ppcart-plan', FILTER_VALIDATE_INT);
$ppcart_manage_request = ppcart_filter_input_request('ppcart-manage', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_slm_order_request = ppcart_filter_input_request('ppcart-slm-order', FILTER_VALIDATE_INT);
$action_request = ppcart_filter_input_request('action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$login_request = ppcart_filter_input_request('login', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$key_request = ppcart_filter_input_request('key', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$error_request = ppcart_filter_input_request('error', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$errors_request = ppcart_filter_input_request('errors', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$checkemail_request = ppcart_filter_input_request('checkemail', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$password_request = ppcart_filter_input_request('password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (is_user_logged_in()) {
    if (!$account_tab) {
        if (false !== $ppcart_order_request && null !== $ppcart_order_request) {
            $attr['order'] = absint($ppcart_order_request);

            if (!$this->verify_user_access($attr['order'], 'order')) {
                wp_die(esc_html__('You do not have permission to access this order.', 'publishpress-cart'));
            }

            $ret = ppcart_get_template('my-account/order', 'detail', $attr);
        } elseif (false !== $ppcart_plan_request && null !== $ppcart_plan_request && 'stripe' === $ppcart_manage_request) {
            $sub = new PPCart_Subscription(absint($ppcart_plan_request));
            $order_data = (object) $sub->get_data();

            $customerId = $order_data->customer_id;

            $apikey = $ppcart_stripe['sk'];

            try {
                $stripe = ppcart_stripe_client($apikey);

                $sessionId = $stripe->billingPortal->sessions->create([
                    'customer' => $customerId,
                    'return_url' => $this->my_account_url,
                ]);

                if ($sessionId && $sessionId->url) {
                    // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe returns an external billing portal URL that wp_safe_redirect() would block.
                    wp_redirect($sessionId->url);
                    exit;
                }
            } catch (\PublishPress\Stripe\Exception\ApiErrorException $e) {
                echo esc_html('Stripe API Error: ' . $e->getMessage());
                exit;
            }
        } elseif (false !== $ppcart_plan_request && null !== $ppcart_plan_request) {
            $attr['plan'] = absint($ppcart_plan_request);

            if (!$this->verify_user_access($attr['plan'], 'subscription')) {
                wp_die(esc_html__('You do not have permission to access this subscription.', 'publishpress-cart'));
            }
            $ret = ppcart_get_template('my-account/subscription', 'detail', $attr);
        } elseif (false !== $ppcart_slm_order_request && null !== $ppcart_slm_order_request) {
            $attr['order'] = absint($ppcart_slm_order_request);

            if (!$this->verify_user_access($attr['order'], 'order')) {
                wp_die(esc_html__('You do not have permission to access this order.', 'publishpress-cart'));
            }

            $ret = ppcart_get_template('my-account/slm-plan', 'detail', $attr);
        } else {
            $ret =  ppcart_get_template('my-account/my-account', '', $attr);
        }
    } elseif ((false === $ppcart_order_request || null === $ppcart_order_request) && (false === $ppcart_plan_request || null === $ppcart_plan_request)) {
        $ret =  '<div class="ppcart-my-account">' . ppcart_get_template('my-account/tabs/' . $account_tab, '', $attr) . '</div>';
    }

    if ($ret = apply_filters('ppcart_my_account_tab_content', $ret)) {
        return $ret;
    }
} elseif (!$account_tab && (!$attr['hide_login'] || $attr['hide_login'] == 'false')) {
    $attr['login_url'] = $this->my_account_url;
    $attr['action'] = is_string($action_request) && '' !== $action_request ? sanitize_text_field($action_request) : 'login';
    $attr['errors'] = [];

    if (is_string($login_request) && '' !== $login_request && is_string($key_request) && '' !== $key_request) {
        // show password reset
        $attr['action'] = 'reset';
        $attr['login'] = sanitize_text_field($login_request);
        $attr['key'] = sanitize_text_field($key_request);

        // Error messages
        if (is_string($error_request) && '' !== $error_request) {
            $error_codes = explode(',', $error_request);

            foreach ($error_codes as $error_code) {
                $attr['errors'][] = $messages[$error_code];
            }
        }
    } elseif ($attr['action'] == 'lostpassword') {
        // Check if the user just requested a new password
        $attr['lost_password_sent'] = is_string($checkemail_request) && 'confirm' === $checkemail_request;

        // Retrieve possible errors from request parameters
        $attr['errors'] = [];
        if (is_string($errors_request) && '' !== $errors_request) {
            $error_codes = explode(',', $errors_request);

            foreach ($error_codes as $error_code) {
                $attr['errors'][] = $messages[$error_code];
            }
        }
    } elseif (is_string($login_request) && '' !== $login_request) {
        $login_errors = [];
        $error_codes = explode(',', $login_request);

        foreach ($error_codes as $error_code) {
            $login_errors[] = $messages[$error_code];
        }
        $attr['errors'] = $login_errors;
    }

    // Check if user just updated password
    $attr['password_updated'] = is_string($password_request) && 'changed' === $password_request;
    return ppcart_get_template('my-account/forms/login', 'form', $attr);
} else {
    return;
}
