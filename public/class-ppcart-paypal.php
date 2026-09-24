<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/paypal/traits/trait-ppcart-paypal-requests.php';

require_once __DIR__ . '/paypal/traits/trait-ppcart-paypal-subscriptions.php';

require_once __DIR__ . '/paypal/traits/trait-ppcart-paypal-settings.php';

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
class PPCart_Paypal extends PPCart_Public
{
    use PPCart_Paypal_Requests_Trait;
    use PPCart_Paypal_Subscriptions_Trait;
    use PPCart_Paypal_Settings_Trait;

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
    private $api_url;

    public static $api_sandbox = 'https://api.sandbox.paypal.com/v1';
    public static $api_production = 'https://api.paypal.com/v1';
    public const SSL_VERIFY_OPTION = '_ppcart_paypal_ssl_verify';
    public const SSL_VERIFY_CONSTANT = 'PPCART_PAYPAL_SSL_VERIFY';


    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of the plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $paypal_enabled = $this->paypal_configured();
        if (empty($paypal_enabled)) {
            return;
        }

        $this->api_url = $this->sandbox_enabled() ? self::$api_sandbox : self::$api_production;

        add_action('template_redirect', [$this, 'paypal_process_payment'], 9999);
        add_action('ppcart_order_refund_paypal', [$this, 'paypal_refund'], 10, 2);
        add_filter('ppcart_cancel_subscription', [$this, 'paypal_cancel_subscription'], 10, 4);
        add_filter('ppcart_subscription_pause_restart', [$this, 'paypal_pause_restart_subscription'], 10, 3);
        add_filter('ppcart_enabled_payment_gateways', [$this, 'maybe_add_paypal_enabled']);
        add_filter('ppcart_payment_methods', [$this, 'maybe_add_paypal_pay_method'], 10, 2);

        add_action('wp_ajax_ppcart_paypal_request', [$this, 'paypal_request']);
        add_action('wp_ajax_nopriv_ppcart_paypal_request', [$this, 'paypal_request']);
    }
}
