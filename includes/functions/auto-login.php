<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Auto-login after purchase.
 *
 * A buyer is logged in only when all of these hold:
 * - auto-login was requested at checkout (form flag `ppcart-auto-login`,
 *   stored on the order as `auto_login`, or the product `autologin` setting);
 * - the account was created by this order (`auto_login_user` order meta),
 *   so buying with someone else's email never logs into their account;
 * - the account has no privileged role;
 * - the order is recent (filter `ppcart_auto_login_window`, default 1 hour);
 * - the order has not already been used for auto-login (one time only).
 *
 * Callers must have verified the visitor owns the checkout (nonce + Order
 * access token, or PayPal completion checks) before calling this.
 */

if (! function_exists('ppcart_request_wants_auto_login')) {
    /**
     * Whether the current checkout request carries the auto-login flag.
     *
     * @return bool
     */
    function ppcart_request_wants_auto_login()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only flag; callers verify the checkout request.
        return isset($_POST['ppcart-auto-login']) && '' !== (string) $_POST['ppcart-auto-login'] && '0' !== (string) $_POST['ppcart-auto-login'];
    }
}

if (! function_exists('ppcart_remember_auto_login_request')) {
    /**
     * Store the checkout auto-login flag on the order, for flows that finish
     * on a later request (PayPal return, upsell steps).
     *
     * @param int $order_id Order post ID.
     * @return void
     */
    function ppcart_remember_auto_login_request($order_id)
    {
        $order_id = absint($order_id);
        if ($order_id && ppcart_request_wants_auto_login()) {
            ppcart_update_post_meta($order_id, 'auto_login', 1);
        }
    }
}

if (! function_exists('ppcart_order_wants_auto_login')) {
    /**
     * Whether auto-login was requested for this order.
     *
     * @param int         $order_id Order post ID.
     * @param object|null $product  Product set up with ppcart_setup_product(), if loaded.
     * @return bool
     */
    function ppcart_order_wants_auto_login($order_id, $product = null)
    {
        $order_id = absint($order_id);
        if (! $order_id) {
            return false;
        }

        if (ppcart_request_wants_auto_login() || ppcart_get_post_meta($order_id, 'auto_login', true)) {
            return true;
        }

        if (is_object($product) && ! empty($product->autologin)) {
            return true;
        }

        $product_id = absint(ppcart_get_post_meta($order_id, 'product_id', true));

        return $product_id && (bool) ppcart_get_post_meta($product_id, 'autologin', true);
    }
}

if (! function_exists('ppcart_maybe_auto_login_user')) {
    /**
     * Log the buyer in after purchase when it is safe to do so.
     *
     * @param int $user_id  User to log in.
     * @param int $order_id Order that created the user.
     * @return bool True when the user was logged in.
     */
    function ppcart_maybe_auto_login_user($user_id, $order_id)
    {
        $user_id  = absint($user_id);
        $order_id = absint($order_id);

        if (! $user_id || ! $order_id || is_user_logged_in()) {
            return false;
        }

        if (! ppcart_order_wants_auto_login($order_id)) {
            return false;
        }

        // Only accounts this order created.
        if ($user_id !== absint(ppcart_get_post_meta($order_id, 'auto_login_user', true))) {
            return false;
        }

        // One time only.
        if (ppcart_get_post_meta($order_id, 'auto_login_done', true)) {
            return false;
        }

        $user = get_userdata($user_id);
        if (! $user) {
            return false;
        }

        foreach ((array) $user->roles as $role) {
            if (ppcart_is_privileged_role($role)) {
                return false;
            }
        }

        if (user_can($user, 'edit_posts') || user_can($user, 'manage_options')) {
            return false;
        }

        $window     = (int) apply_filters('ppcart_auto_login_window', HOUR_IN_SECONDS, $order_id, $user_id);
        $order_time = (int) get_post_time('U', true, $order_id);
        if (! $order_time || (time() - $order_time) > $window) {
            return false;
        }

        if (! apply_filters('ppcart_allow_auto_login', true, $user_id, $order_id)) {
            return false;
        }

        ppcart_update_post_meta($order_id, 'auto_login_done', time());

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, false, is_ssl());

        /* translators: %s: user ID. */
        ppcart_log_entry($order_id, sprintf(__('Customer logged in automatically after purchase (User ID: %s)', 'publishpress-cart'), $user_id));
        do_action('ppcart_after_auto_login', $user_id, $order_id);

        return true;
    }
}

if (! function_exists('ppcart_maybe_auto_login_order_buyer')) {
    /**
     * Log in the buyer of a paid order on a verified checkout completion request.
     *
     * @param int         $order_id Order post ID.
     * @param object|null $product  Product, if loaded.
     * @return bool
     */
    function ppcart_maybe_auto_login_order_buyer($order_id, $product = null)
    {
        $order_id = absint($order_id);
        if (! $order_id || is_user_logged_in() || ! ppcart_order_wants_auto_login($order_id, $product)) {
            return false;
        }

        // The order model maps the stored post status (e.g. ppcart_paid) to its canonical name.
        $order  = new PPCart_Order($order_id);
        $status = (string) $order->status;
        if (! in_array($status, [ 'paid', 'completed' ], true)) {
            return false;
        }

        $user_id = absint(ppcart_get_post_meta($order_id, 'user_account', true));

        return ppcart_maybe_auto_login_user($user_id, $order_id);
    }
}
