<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Product_Admin
{
    /**
     * The prefix of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $stripe    The current version of this plugin.
     */
    private $stripe;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct()
    {
    }

    /**
     * Load the required dependencies for the Admin facing functionality.
     *
     * Include the following files that make up the plugin:
     *
     * - NC_Cart_Admin_Settings. Registers the admin settings and page.
     *
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-admin-settings.php';
    }

    public function save_stripe_objects($post_id, $objects)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-admin-save-stripe-objects.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_stripe_product($post_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-admin-get-stripe-product.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function create_stripe_product($post_id)
    {
        $stripe = $this->stripe;
        try {
            $product = $stripe->products->create([
              'name' => ppcart_get_public_product_name($post_id),
              'description' => ppcart_get_public_product_name($post_id),
              'metadata'    => ['ppcart_product_id' => $post_id, 'origin' => get_site_url()],
            ]);
            ppcart_update_post_meta($post_id, 'stripe_prod_id', $product->id);
            return $product;
        } catch (Exception $e) {
            echo esc_html($e->getMessage());
            exit;
        }
    }

    public function create_plan($plan, $post_id, $stripe_prod_id)
    {
        global $ppcart_currency;

        $stripe = $this->stripe;

        $recurring = ['interval' => $plan['interval'], 'interval_count' => $plan['frequency']];

        try {
            $stripe_plan = $stripe->prices->create([
              'unit_amount' => ppcart_price_in_cents($plan['amount'], $ppcart_currency), // dollars to cents
              'currency'    => $ppcart_currency,
              'recurring'   => $recurring,
              'product'     => $stripe_prod_id,
              'metadata'    => ['ppcart_product_id' => $post_id, 'origin' => get_site_url()],
            ]);
            return $stripe_plan->id;
        } catch (Exception $e) {
            echo esc_html($e->getMessage());
            exit;
        }
    }

    private function format_coupon_id($id)
    {
        $id = str_replace(' ', '_', $id);
        $id = str_replace('-', '_', $id);
        return preg_replace('/[^A-Za-z0-9\_]/', '', $id);
    }
}
