<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package PPCart
 * @subpackage PPCart/public
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Public
{
    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $version    URL to user selected my account/login page.
     */
    private $my_account_url;

    /**
     * The prefix of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $prefix    The current version of this plugin.
     */
    public $prefix;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of the plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct($plugin_name = '', $version = '', $prefix = '')
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->prefix = $prefix;
        new PPCart_Public_Account_Controller();

        add_action('ppcart_order_pending', [$this, 'do_order_integration_functions'], 10, 3);
        add_action('ppcart_order_complete', [$this, 'do_order_complete_functions'], 10, 3);
        add_action('ppcart_order_refunded', [$this, 'do_order_integration_functions'], 10, 3);

        add_action('ppcart_renewal_payment', [$this, 'do_order_integration_functions'], 10, 3);
        add_action('ppcart_renewal_failed', [$this, 'do_order_integration_functions'], 10, 3);
        add_action('ppcart_renewal_uncollectible', [$this, 'do_order_integration_functions'], 10, 3);

        add_action('ppcart_subscription_active', [$this, 'do_order_integration_functions'], 10, 3);
        add_action('ppcart_subscription_canceled', [$this, 'do_order_integration_functions'], 10, 3);
        add_action('ppcart_subscription_paused', [$this, 'do_order_integration_functions'], 10, 3);
        add_action('ppcart_subscription_past_due', [$this, 'do_order_integration_functions'], 10, 3);
        add_action('ppcart_subscription_completed', [$this, 'do_order_integration_functions'], 10, 3);

        add_action('ppcart_run_after_integrations', [$this, 'do_after_integration_functions'], 10, 3);
    }

    /**
     * Do order integrations
     *
     * @since 1.0.0
     */

    public function do_order_integration_functions($status, $order_data, $order_type = 'main')
    {

        ppcart_do_integrations($order_data['product_id'], $order_data, $status);

        if ($status == 'pending' && $order_data['pay_method'] == 'cod') {
            ppcart_do_notifications($order_data);
        }
    }

    public function do_after_integration_functions($status, $order_data, $event_type = 'order')
    {
        do_action('ppcart_after_order_paid', $status, $order_data, $event_type);

        $type = ($status == 'paid') ? 'purchase' : $status;
        if (!ppcart_get_post_meta($order_data['product_id'], 'disable_' . $type . '_email', true)) {
            ppcart_notification_send($status, $order_data);
        }
    }

    /**
     * Do order complete functions.
     *
     * @since 1.0.0
     */
    public function do_order_complete_functions($status, $order_data, $order_type = 'main')
    {
        ppcart_do_integrations($order_data['product_id'], $order_data);
        ppcart_maybe_update_stock($order_data['product_id']);
        ppcart_do_notifications($order_data);
    }

    /**
     * Compatibility callback for legacy code that still registers this object
     * on WordPress title filters.
     *
     * @param string      $title Current title.
     * @param int|WP_Post $id    Current post ID or object.
     * @return string
     */
    public function public_product_name($title, $id = null)
    {
        if (! $id) {
            $id = get_the_ID();
        } elseif (is_object($id) && isset($id->ID)) {
            $id = $id->ID;
        }

        if (! is_admin()) {
            $post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
            if (in_array(get_post_type($id), $post_types, true)) {
                $name = ppcart_get_post_meta($id, 'product_name', true);
                if ($name) {
                    return esc_html(ppcart_normalize_product_name($name));
                }
            }
        }

        return $title;
    }

    /**
     * Returns Stripe Connect destination and fee configuration for compatibility callers.
     */
    public static function get_stripe_connect_config()
    {
        return PPCart_Public_Checkout_Controller::get_stripe_connect_config();
    }

    public static function get_stripe_resource_value($resource, $key, $default = null)
    {
        return PPCart_Public_Checkout_Controller::get_stripe_resource_value($resource, $key, $default);
    }

    public static function get_stripe_resource_id($resource)
    {
        return PPCart_Public_Checkout_Controller::get_stripe_resource_id($resource);
    }

    /**
     * Handles invoice downloads.
     *
     * The method name is misleading legacy naming. Invoice download is shared
     * Free functionality because Free owns invoice links, query vars, and output.
     */
    public function stripe_webhook()
    {
        $gateway = get_query_var('ppcart-api');
        if (! $gateway) {
            $gateway = ppcart_filter_input_request('ppcart-api', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        if (! $gateway && ! empty($_SERVER['REQUEST_URI'])) {
            $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
            if (preg_match('#(?:^|/)ppcart-webhook/([^/?]+)#', $request_uri, $matches)) {
                $gateway = $matches[1];
            }
        }

        if ($gateway) {
            $gateway = sanitize_key($gateway);
            if (file_exists(plugin_dir_path(__FILE__) . 'webhooks/' . $gateway . '.php')) {
                require(plugin_dir_path(__FILE__) . 'webhooks/' . $gateway . '.php');
            } else {
                do_action('ppcart_gateway_webhook');
            }
        }
    }

    public function webhook_rewrite_rule()
    {
        $page_slug = 'ppcart-webhook'; // slug of the page you want to be shown to
        $param     = 'ppcart-api';       // param name you want to handle on the page

        add_rewrite_rule('ppcart-webhook/?([^/]*)', 'index.php?' . $param . '=$matches[1]', 'top');
        add_rewrite_endpoint('ppcart-api', EP_ROOT);
    }

    public function api_query_vars($qvars)
    {
        $qvars[] = 'ppcart-api';
        $qvars[] = 'ppcart-csv-export';
        $qvars[] = 'ppcart-invoice';
        return $qvars;
    }

    /**
     * Creates a Stripe SetupIntent for the Payment Element subscription flow.
     *
     * @return void
     */
    /**
     * Creates a Stripe-hosted Checkout Session and returns its redirect URL.
     *
     * @return void
     */
    /**
     * Recalculates checkout totals for the selected product and payment options.
     */
    /**
     * Update customer's stripe card
     */
    /**
     * Display customer's default payment method on subscription view
     */
}
