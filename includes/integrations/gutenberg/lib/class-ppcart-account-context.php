<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Account_Context
{
    public function can_view_account_detail()
    {
        return is_user_logged_in();
    }

    public function get_account_detail_return_url()
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $account_url = $this->get_my_account_url();

            if (is_string($account_url) && '' !== $account_url) {
                return $account_url;
            }
        }

        $return_url = remove_query_arg([ 'ppcart-plan', 'ppcart-order', 'ppcart-manage', 'action' ]);

        if (is_string($return_url) && '' !== $return_url) {
            return $return_url;
        }

        return $this->get_my_account_url();
    }

    public function sanitize_account_detail_return_url($return_url)
    {
        if (! is_string($return_url) || '' === $return_url) {
            return $this->get_account_detail_return_url();
        }

        $return_url = remove_query_arg(
            [ 'ppcart-plan', 'ppcart-order', 'ppcart-manage', 'action' ],
            esc_url_raw($return_url)
        );

        return wp_validate_redirect($return_url, $this->get_my_account_url());
    }

    public function is_editor_preview()
    {
        if (! defined('REST_REQUEST') || ! REST_REQUEST || ! current_user_can('edit_posts')) {
            return false;
        }

        $route = $this->get_current_rest_route();

        return '' !== $route && false !== strpos($route, '/wp/v2/block-renderer');
    }

    public function get_login_template_attributes()
    {
        $messages           = function_exists('ppcart_translate_js') ? ppcart_translate_js('ppcart-public.js') : [];
        $action_request     = ppcart_filter_input_request('action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $login_request      = ppcart_filter_input_request('login', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $key_request        = ppcart_filter_input_request('key', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $error_request      = ppcart_filter_input_request('error', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $errors_request     = ppcart_filter_input_request('errors', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $checkemail_request = ppcart_filter_input_request('checkemail', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password_request   = ppcart_filter_input_request('password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $attributes         = [
            'login_url'        => $this->get_my_account_url(),
            'action'           => is_string($action_request) && '' !== $action_request ? sanitize_text_field($action_request) : 'login',
            'errors'           => [],
            'password_updated' => is_string($password_request) && 'changed' === $password_request,
        ];

        if (is_string($login_request) && '' !== $login_request && is_string($key_request) && '' !== $key_request) {
            $attributes['action'] = 'reset';
            $attributes['login']  = sanitize_text_field($login_request);
            $attributes['key']    = sanitize_text_field($key_request);

            if (is_string($error_request) && '' !== $error_request) {
                $attributes['errors'] = $this->get_messages_from_codes(explode(',', $error_request), $messages);
            }
        } elseif ('lostpassword' === $attributes['action']) {
            $attributes['lost_password_sent'] = is_string($checkemail_request) && 'confirm' === $checkemail_request;

            if (is_string($errors_request) && '' !== $errors_request) {
                $attributes['errors'] = $this->get_messages_from_codes(explode(',', $errors_request), $messages);
            }
        } elseif (is_string($login_request) && '' !== $login_request) {
            $attributes['errors'] = $this->get_messages_from_codes(explode(',', $login_request), $messages);
        }

        return $attributes;
    }

    public function get_request_or_attribute_id($request_key, $attribute_key, $attributes)
    {
        $request_id = $this->get_request_id($request_key);

        if ($request_id) {
            return $request_id;
        }

        return isset($attributes[ $attribute_key ]) ? absint($attributes[ $attribute_key ]) : 0;
    }

    public function get_request_id($request_key)
    {
        $request_id = ppcart_filter_input_request($request_key, FILTER_VALIDATE_INT);

        if (false !== $request_id && null !== $request_id) {
            return absint($request_id);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only account routing supports query parameters such as ppcart-order and ppcart-plan.
        if (isset($_REQUEST[ $request_key ])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only account routing supports query parameters such as ppcart-order and ppcart-plan.
            $request_id = filter_var(wp_unslash($_REQUEST[ $request_key ]), FILTER_VALIDATE_INT);

            if (false !== $request_id && null !== $request_id) {
                return absint($request_id);
            }
        }

        return 0;
    }

    public function get_request_string($request_key)
    {
        $request_value = ppcart_filter_input_request($request_key, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (is_string($request_value) && '' !== $request_value) {
            return sanitize_key($request_value);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only account routing supports query parameters such as action and ppcart-manage.
        if (isset($_REQUEST[ $request_key ])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only account routing supports query parameters such as action and ppcart-manage.
            return sanitize_key(wp_unslash($_REQUEST[ $request_key ]));
        }

        return '';
    }

    public function get_detail_route_active_tab()
    {
        if ($this->get_request_id('ppcart-order')) {
            return 'tab-orders';
        }

        if ($this->get_request_id('ppcart-plan')) {
            return 'tab-subscriptions';
        }

        return '';
    }

    public function verify_user_access($id, $type = 'subscription')
    {
        if (! is_user_logged_in()) {
            return false;
        }

        $object  = 'subscription' === $type ? new PPCart_Subscription($id) : new PPCart_Order($id);
        $data    = $object->get_data();
        $user_id = 0;

        if (is_array($data) && isset($data['user_account'])) {
            $user_id = absint($data['user_account']);
        } elseif (is_object($data) && isset($data->user_account)) {
            $user_id = absint($data->user_account);
        }

        return $user_id && get_current_user_id() === $user_id;
    }

    public function get_my_account_url()
    {
        if ($pid = get_option('_ppcart_myaccount_page_id')) {
            return get_permalink($pid);
        }

        return false;
    }

    public function get_account_navigation_options()
    {
        $options = [
            [
                'label' => __('Orders', 'publishpress-cart'),
                'value' => 'tab-orders',
            ],
            [
                'label' => __('Subscriptions', 'publishpress-cart'),
                'value' => 'tab-subscriptions',
            ],
            [
                'label' => __('Installment Plans', 'publishpress-cart'),
                'value' => 'tab-plans',
            ],
            [
                'label' => __('My Profile', 'publishpress-cart'),
                'value' => 'tab-profile',
            ],
            [
                'label' => apply_filters('ppcart_download_tab_name', __('Downloads', 'publishpress-cart')),
                'value' => 'tab-files',
            ],
        ];

        return apply_filters('ppcart_account_block_navigation_options', $options);
    }

    private function get_current_rest_route()
    {
        $route = '';
        global $wp;

        if (isset($wp->query_vars['rest_route']) && is_string($wp->query_vars['rest_route'])) {
            $route = $wp->query_vars['rest_route'];
        }

        if ('' === $route) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route detection for REST preview handling.
            $request_route = isset($_GET['rest_route']) ? sanitize_text_field(wp_unslash($_GET['rest_route'])) : '';

            if (is_string($request_route)) {
                $route = $request_route;
            }
        }

        if ('' === $route && isset($_SERVER['REQUEST_URI'])) {
            $request_uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
            $path        = wp_parse_url($request_uri, PHP_URL_PATH);
            $prefix      = '/' . rest_get_url_prefix() . '/';

            if (is_string($path) && false !== strpos($path, $prefix)) {
                $route = '/' . ltrim(substr($path, strpos($path, $prefix) + strlen($prefix)), '/');
            }
        }

        return is_string($route) ? '/' . ltrim($route, '/') : '';
    }

    private function get_messages_from_codes($codes, $messages)
    {
        $errors = [];

        foreach ($codes as $error_code) {
            $error_code = sanitize_key($error_code);

            if (isset($messages[ $error_code ])) {
                $errors[] = $messages[ $error_code ];
            }
        }

        return $errors;
    }
}
