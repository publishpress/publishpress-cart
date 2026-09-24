<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/traits/trait-ppcart-public-account-shortcodes.php';

require_once __DIR__ . '/traits/trait-ppcart-public-account-passwords.php';

/**
 * Customer account and login flow.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Registers account shortcodes, login redirects, and customer portal screens.
 */
class PPCart_Public_Account_Controller
{
    use PPCart_Public_Account_Shortcodes_Trait;
    use PPCart_Public_Account_Passwords_Trait;

    /**
         * URL for the configured customer account page.
         *
         * @var string|false
         */
    private $my_account_url;

    public function __construct()
    {
        add_action('init', [$this, 'set_my_account_url'], 1);

        add_action('login_form_bottom', [$this, 'add_lost_password_link']);
        add_action('login_form_lostpassword', [ $this, 'do_password_lost' ]);
        add_action('login_form_rp', [ $this, 'do_password_reset' ]);
        add_action('login_form_resetpass', [ $this, 'do_password_reset' ]);
        add_action('login_form_rp', [ $this, 'redirect_to_custom_password_reset' ]);
        add_action('login_form_resetpass', [ $this, 'redirect_to_custom_password_reset' ]);
        add_filter('authenticate', [ $this, 'maybe_redirect_at_authenticate' ], 101, 3);
        add_filter('retrieve_password_message', [ $this, 'custom_password_reset_email' ], 10, 4);
        add_action('wp_logout', [ $this, 'redirect_to_custom_logout_link' ]);
        add_filter('ppcart_send_new_user_email', [ $this, 'maybe_disable_welcome_email' ], 101, 3);

        add_filter('body_class', [ $this, 'add_shortcode_specific_body_class' ]);
        add_shortcode('ppcart_account', [ $this, 'my_account_page_shortcode' ]);
        add_shortcode('ppcart_account_link', [ $this, 'my_account_page_link_shortcode' ]);
        add_shortcode('ppcart_account_order_detail', [ $this, 'order_detail_shortcode' ]);
        add_shortcode('ppcart_account_subscription_detail', [ $this, 'subscription_detail_shortcode' ]);
    }
    private function get_template($template_name, $attr = null)
    {
        if (! $attr) {
            $attr = [];
        }

        ob_start();
        do_action('ppcart_login_before_' . $template_name);
        require dirname(__DIR__) . '/templates/' . $template_name . '.php';
        do_action('ppcart_login_after_' . $template_name);

        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function set_my_account_url()
    {
        $this->my_account_url = $this->get_my_account_url();
    }

    public function get_my_account_url()
    {

        if ($pid = get_option('_ppcart_myaccount_page_id')) {
            return get_permalink($pid);
        }

        return false;
    }

    public function add_lost_password_link()
    {
        if (get_the_ID() != get_option('_ppcart_myaccount_page_id')) {
            return;
        }

        return '<a href="' . esc_url('?action=lostpassword') . '">' . esc_html__('Forgot your password?', 'publishpress-cart') . '</a>';
    }











    public function redirect_to_custom_logout_link()
    {
        if (! empty($this->my_account_url)) {
            wp_safe_redirect($this->my_account_url);
            exit;
        }
    }

    public function maybe_use_default_login_authentication($return = false)
    {
        $login_url = $this->my_account_url;
        $http_referer = filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_URL);
        $return = (! $this->my_account_url || ! is_string($http_referer) || strtok($http_referer, '?') != $login_url);
        return apply_filters('ppcart_use_default_authentication_logic', $return);
    }

    public function maybe_redirect_at_authenticate($user, $username, $password)
    {
        // Check if the earlier authenticate filter (most likely,
        // the default WordPress authentication) functions have found errors
        $login_url = $this->my_account_url;
        if ($this->maybe_use_default_login_authentication()) {
            return $user;
        }

        $request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ('POST' === $request_method) {
            if (is_wp_error($user)) {
                $error_codes = join(',', $user->get_error_codes());

                $login_url = add_query_arg('login', $error_codes, $login_url);

                wp_safe_redirect($login_url);
                exit;
            }
        }

        return $user;
    }

    public function maybe_disable_welcome_email($send_email, $order_id)
    {
        $pid = PPCart_Order::get_meta_value($order_id, 'product_id');
        if (ppcart_get_post_meta($pid, 'disable_welcome_email', true)) {
            $send_email = false;
        }
        return $send_email;
    }
}
