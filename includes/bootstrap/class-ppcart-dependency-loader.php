<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Loads plugin class files and returns the loader, sanitizer, and Stripe product admin instances.
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */
class PPCart_Dependency_Loader
{
    /**
     * Require core, admin, public, and integration files, then return shared instances.
     *
     * @param PPCart $cart Plugin bootstrap instance.
     * @return array Associative array with loader, sanitizer, and stripe_product instances.
     */
    public static function load($cart)
    {

        global $ppcart_public;

        $base_path = plugin_dir_path(dirname(dirname(__FILE__)));

        require_once $base_path . 'includes/bootstrap/class-ppcart-admin-hook-registrar.php';
        require_once $base_path . 'includes/bootstrap/class-ppcart-public-hook-registrar.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
        */
        require_once $base_path . 'includes/class-ppcart-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once $base_path . 'includes/class-ppcart-i18n.php';

        /**
         * The class responsible for centralized admin screen detection.
         */
        require_once $base_path . 'includes/class-ppcart-admin-screens.php';

        /**
         * The class responsible for duplicating product post configuration.
         */
        require_once $base_path . 'includes/class-ppcart-product-duplicator.php';

        /**
         * The class responsible for initializing the debug logger.
         */
        require_once $base_path . 'includes/logging/class-ppcart-debug-logger.php';
        global $ppcart_debug_logger;
        $ppcart_debug_logger = new PPCart_Debug_Logger();
        if (class_exists('PPCart_Stripe_Webhook_Logger')) {
            PPCart_Stripe_Webhook_Logger::init();
        }

        if (class_exists('PPCart_Secrets')) {
            PPCart_Secrets::init();
        }

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once $base_path . 'admin/class-ppcart-admin.php';
        require_once $base_path . 'admin/controllers/class-ppcart-admin-order-controller.php';
        require_once $base_path . 'admin/controllers/class-ppcart-admin-subscription-controller.php';
        require_once $base_path . 'admin/controllers/class-ppcart-admin-order-list-controller.php';
        require_once $base_path . 'admin/controllers/class-ppcart-admin-order-refund-controller.php';
        require_once $base_path . 'admin/controllers/class-ppcart-admin-test-mode-notice-controller.php';
        require_once $base_path . 'admin/controllers/class-ppcart-admin-page-notices.php';
        require_once $base_path . 'admin/controllers/class-ppcart-admin-legacy-tracking-notice-controller.php';

        /**
         * The class responsible for defining all actions that occur in the add admin product area.
         */
        require_once $base_path . 'admin/class-ppcart-product-admin.php';


        /**
         * The class responsible for defining all admin product fields.
         */
        require_once $base_path . 'admin/metaboxes/class-ppcart-product-metabox-option-sources.php';
        require_once $base_path . 'admin/class-ppcart-product-metaboxes.php';

        /**
         * The class responsible for defining all admin order fields.
         */
        require_once $base_path . 'admin/class-ppcart-order-metaboxes.php';

        /**
         * File Downloads
         */
        require_once $base_path . 'includes/files/class-ppcart-files.php';

        /**
         * Order Items
         */
        require_once $base_path . 'includes/order-items/class-ppcart-order-items.php';

        /**
         * Custom Post Types and Taxonomies
         */
        require_once $base_path . 'includes/class-ppcart-post-types.php';

        /**
         * The class responsible for defining all actions that occur in the product add admin new order area.
         */
        require_once $base_path . 'admin/class-ppcart-order-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
        */
        require_once $base_path . 'public/controllers/class-ppcart-public-account-controller.php';
        require_once $base_path . 'public/controllers/class-ppcart-public-asset-controller.php';
        require_once $base_path . 'public/controllers/class-ppcart-public-subscription-checkout-controller.php';
        require_once $base_path . 'public/controllers/class-ppcart-public-payment-controller.php';
        require_once $base_path . 'public/controllers/class-ppcart-public-hosted-checkout-controller.php';
        require_once $base_path . 'public/controllers/class-ppcart-public-order-controller.php';
        require_once $base_path . 'public/controllers/class-ppcart-public-checkout-controller.php';
        require_once $base_path . 'public/controllers/class-ppcart-public-page-controller.php';
        require_once $base_path . 'public/class-ppcart-public.php';
        $ppcart_public = new PPCart_Public($cart->get_plugin_name(), $cart->get_version(), $cart->get_prefix());

        require_once $base_path . 'public/class-ppcart-paypal.php';
        $ppcart_paypal_class = apply_filters('ppcart_paypal_class', PPCart_Paypal::class);
        if (! is_string($ppcart_paypal_class) || ! class_exists($ppcart_paypal_class)) {
            $ppcart_paypal_class = PPCart_Paypal::class;
        }
        $ppcart_paypal = new $ppcart_paypal_class($cart->get_plugin_name(), $cart->get_version());

        /**
         * The class responsible for sanitizing user input
         */
        require_once $base_path . 'includes/class-ppcart-sanitize.php';

        /**
        * The class responsible for stripe services
        */
        require_once $base_path . 'includes/class-ppcart-stripe.php';

        /**
         * The class responsible for admin ajax
         */
        require_once $base_path . 'admin/class-ppcart-admin-ajax.php';

        // Tax computation/data layer (PPCart_Tax) is Pro-only and loaded by the Pro bootstrap.

        /**
         * The class responsible for the shared Stripe order/subscription save execution.
         */
        require_once $base_path . 'includes/class-ppcart-stripe-save-helper.php';

        /**
         * Class for common helper functions
         */
        require_once $base_path . 'includes/helpers/ppcart-general-functions.php';
        require_once $base_path . 'includes/helpers/ppcart-hosted-checkout.php';
        require_once $base_path . 'includes/helpers/ppcart-stripe-metadata.php';
        require_once $base_path . 'includes/helpers/ppcart-stripe-client.php';
        require_once $base_path . 'includes/helpers/class-ppcart-order-helper.php';
        require_once $base_path . 'includes/helpers/class-ppcart-helper.php';

        /*
        * Gutenberg checkout block
        */
        require_once $base_path . 'includes/integrations/gutenberg/index.php';

        do_action('ppcart_load_pro_modules', $cart);
        return [
            'loader'         => new PPCart_Loader(),
            'sanitizer'      => new PPCart_Sanitize(),
            'stripe_product' => new PPCart_Product_Admin(),
        ];
    }
}
