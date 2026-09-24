<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Public_Hook_Registrar
{
    private $loader;

    private $cart;

    public function __construct($loader, $cart)
    {
        $this->loader = $loader;
        $this->cart   = $cart;
    }

    public function register()
    {
        global $ppcart_public;

        $plugin_public = $ppcart_public;
        $plugin_public_assets = new PPCart_Public_Asset_Controller($this->cart->get_plugin_name(), $this->cart->get_version(), $this->cart->get_prefix());
        $plugin_public_subscriptions = PPCart_Public_Subscription_Checkout_Controller::instance();
        $plugin_public_payment = new PPCart_Public_Payment_Controller();
        $plugin_public_hosted_checkout = new PPCart_Public_Hosted_Checkout_Controller();
        $plugin_public_order = new PPCart_Public_Order_Controller();
        $plugin_public_checkout = new PPCart_Public_Checkout_Controller();
        $plugin_public_page = new PPCart_Public_Page_Controller($this->cart->get_plugin_name(), $this->cart->get_version(), $this->cart->get_prefix());

        $this->loader->add_filter('single_template', $plugin_public_page, 'product_template');
        $this->loader->add_filter('wp', $plugin_public_page, 'email_preview_template');
        $this->loader->add_filter('admin_init', $plugin_public_page, 'email_preview_template');
        $this->loader->add_filter('query_vars', $plugin_public_page, 'query_vars');

        $this->loader->add_action('template_redirect', $plugin_public_page, 'hosted_checkout_return', 5);
        $this->loader->add_action('template_redirect', $plugin_public_page, 'redirect');
        $this->loader->add_filter('ppcart_checkout_complete', $plugin_public_order, 'conditional_order_confirmations', 10, 3);

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public_assets, 'enqueue_styles', 10);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public_assets, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public_checkout, 'process_payment', 9999);

        $this->loader->add_filter('the_title', $plugin_public_page, 'public_product_name', 10, 2);
        $this->loader->add_filter('wp_title', $plugin_public_page, 'public_product_name', 10, 2);
        $this->loader->add_filter('single_post_title', $plugin_public_page, 'public_product_name', 10, 2);

        // stripe webhook
        $this->loader->add_filter('init', $plugin_public, 'webhook_rewrite_rule');
        $this->loader->add_action('init', $plugin_public, 'stripe_webhook', 20);
        $this->loader->add_filter('query_vars', $plugin_public, 'api_query_vars');
        $this->loader->add_action('template_redirect', $plugin_public, 'stripe_webhook');

        $this->loader->add_action('template_redirect', $plugin_public_page, 'customer_csv_export');

        $this->loader->add_action('wp_ajax_ppcart_save_order_to_db', $plugin_public_order, 'save_order_to_db');
        $this->loader->add_action('wp_ajax_nopriv_ppcart_save_order_to_db', $plugin_public_order, 'save_order_to_db');
        $this->loader->add_action('wp_ajax_ppcart_update_stripe_order_status', $plugin_public_order, 'update_stripe_order_status');
        $this->loader->add_action('wp_ajax_nopriv_ppcart_update_stripe_order_status', $plugin_public_order, 'update_stripe_order_status');

        $this->loader->add_action('wp_ajax_ppcart_create_payment_intent', $plugin_public_payment, 'create_payment_intent');
        $this->loader->add_action('wp_ajax_nopriv_ppcart_create_payment_intent', $plugin_public_payment, 'create_payment_intent');

        $this->loader->add_action('wp_ajax_ppcart_create_subscription', $plugin_public_subscriptions, 'create_subscription');
        $this->loader->add_action('wp_ajax_nopriv_ppcart_create_subscription', $plugin_public_subscriptions, 'create_subscription');

        $this->loader->add_action('wp_ajax_ppcart_create_checkout_session', $plugin_public_hosted_checkout, 'create_checkout_session');
        $this->loader->add_action('wp_ajax_nopriv_ppcart_create_checkout_session', $plugin_public_hosted_checkout, 'create_checkout_session');
        $this->loader->add_action('wp_ajax_ppcart_create_setup_intent', $plugin_public_payment, 'create_setup_intent');
        $this->loader->add_action('wp_ajax_nopriv_ppcart_create_setup_intent', $plugin_public_payment, 'create_setup_intent');
        $this->loader->add_action('wp_ajax_ppcart_update_payment_intent_amt', $plugin_public_payment, 'update_payment_intent_amt');
        $this->loader->add_action('wp_ajax_nopriv_ppcart_update_payment_intent_amt', $plugin_public_payment, 'update_payment_intent_amt');

        // VAT applicability AJAX is Pro-only; Pro registers it via ppcart_register_public_ajax_handlers.

        /** Update and display strpe card in my-account */
        $this->loader->add_action('wp_ajax_ppcart_update_stripe_payment_method', $plugin_public_order, 'update_stripe_card');
        $this->loader->add_action('ppcart_show_stripe_payment_method', $plugin_public_order, 'show_stripe_payment_method', 10, 1);

        $this->loader->add_action('template_redirect', $plugin_public_page, 'invoices_download');

        do_action('ppcart_register_public_ajax_handlers', $this->loader, $plugin_public);
    }
}
