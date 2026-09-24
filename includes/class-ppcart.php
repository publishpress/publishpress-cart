<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since 1.0.0
 * @package PPCart
 * @subpackage PPCart/includes
 * @author PublishPress <help@publishpress.com>
 */
class PPCart
{
    /**
     * The prefix used by the plugin.
     *
     * @access public
     * @var string    $prefix    The prefix used for various plugin identifiers.
     */
    public $prefix;

    /**
    * The sanitizer instance for handling user input sanitization.
    *
    * @access protected
    * @var PPCart_Sanitize    $sanitizer    The sanitizer instance.
    */
    protected $sanitizer;

    /**
    * The Stripe product admin instance for managing Stripe products.
    *
    * @access protected
    * @var PPCart_Product_Admin    $stripe_product    The Stripe product admin instance.
    */
    protected $stripe_product;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var PPCart_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The title of this plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var string    $plugin_title    The string used to uniquely identify this plugin.
     */
    protected $plugin_title;

    /**
     * The current version of the plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function __construct()
    {

        if (defined('PPCART_VERSION')) {
            $this->version = PPCART_VERSION;
        } else {
            $this->version = '1.0';
        }

        $this->prefix = 'ppcart_';

        // bug fix added v2.0.152
        if ($key = get_option('ppcart_api_key')) {
            update_option('_ppcart_api_key', $key);
            delete_option('ppcart_api_key');
        }

        if (get_option('_ppcart_decimal_number') === false) {
            update_option('_ppcart_decimal_number', 2);
        }

        $this->plugin_name = 'ppcart';
        $this->plugin_title = 'Cart';

        $this->load_dependencies();
        if (class_exists('PPCart_Post_Status_Sync')) {
            PPCart_Post_Status_Sync::register();
        }
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        foreach (glob(plugin_dir_path(__FILE__) . "/integrations/*.php") as $filename) {
            // load all integrations
            include $filename;
            $classes = get_declared_classes();
            $class = end($classes);
            $class_name = explode('\\', $class);
            $class_var = strtolower(end($class_name));
            $$class_var   = new $class();
        }

        do_action('ppcart_register_pro_hooks', $this->loader, $this);

        do_action('ppcart_before_load');
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - PPCart_Loader. Orchestrates the hooks of the plugin.
     * - PPCart_I18n. Defines internationalization functionality.
     * - PPCart_Admin. Defines all hooks for the admin area.
     * - PPCart_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/bootstrap/class-ppcart-dependency-loader.php';

        $dependencies = PPCart_Dependency_Loader::load($this);

        $this->loader         = $dependencies['loader'];
        $this->sanitizer      = $dependencies['sanitizer'];
        $this->stripe_product = $dependencies['stripe_product'];
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the PPCart_I18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since 1.0.0
     * @access private
     */
    private function set_locale()
    {

        $plugin_i18n = new PPCart_I18n();

        $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain', 0);
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_admin_hooks()
    {
        $registrar = new PPCart_Admin_Hook_Registrar($this->loader, $this);
        $registrar->register();
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_public_hooks()
    {
        $registrar = new PPCart_Public_Hook_Registrar($this->loader, $this);
        $registrar->register();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since 1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The name of the plugin used to identify it in the frontend.
     *
     * @since 1.0.0
     * @return    string    The nice name of the plugin.
     */
    public function get_plugin_title()
    {
        return $this->plugin_title;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since 1.0.0
     * @return    PPCart_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since 1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Retrieve the prefix  of the plugin.
     *
     * @since 1.0.0
     * @return    string    The prefix of the plugin.
     */
    public function get_prefix()
    {
        return $this->prefix;
    }
}
