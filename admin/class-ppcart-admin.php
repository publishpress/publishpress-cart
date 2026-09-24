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
require_once plugin_dir_path(__FILE__) . 'traits/trait-ppcart-admin-notices.php';
require_once plugin_dir_path(__FILE__) . 'traits/trait-ppcart-admin-product-duplicate.php';
require_once plugin_dir_path(__FILE__) . 'traits/trait-ppcart-admin-privacy.php';
require_once plugin_dir_path(__FILE__) . 'traits/trait-ppcart-admin-assets.php';
require_once plugin_dir_path(__FILE__) . 'traits/trait-ppcart-admin-integrations.php';
require_once plugin_dir_path(__FILE__) . 'traits/trait-ppcart-admin-profile.php';
require_once plugin_dir_path(__FILE__) . 'traits/trait-ppcart-admin-stripe-portal.php';
class PPCart_Admin
{
    use PPCart_Admin_Notices_Trait;
    use PPCart_Admin_Product_Duplicate_Trait;
    use PPCart_Admin_Privacy_Trait;
    use PPCart_Admin_Assets_Trait;
    use PPCart_Admin_Integrations_Trait;
    use PPCart_Admin_Profile_Trait;
    use PPCart_Admin_Stripe_Portal_Trait;

    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The title of this plugin.
     *
     * @access private
     * @var string    $plugin_title    The display title of this plugin.
     */
    private $plugin_title;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The Stripe client instance for API interactions.
     *
     * @access private
     * @var \PublishPress\Stripe\StripeClient|null    $stripe    The Stripe client or null if not configured.
     */
    private $stripe;


    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $plugin_title, $version)
    {
        global $ppcart_stripe;
        if (empty($ppcart_stripe['sk'])) {
            $this->stripe = null;
        } else {
            $this->stripe = ppcart_stripe_client($ppcart_stripe['sk']);
        }

        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;

        $this->load_dependencies();
    }

    /**
     * Load the required dependencies for the Admin facing functionality.
     *
     * Include the following files that make up the plugin:
     *
     * - PPCart_Admin_Settings. Registers the admin settings and page.
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
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-admin-sidebar.php';
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-admin-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/dashboard/class-ppcart-dashboard-widget.php';
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-admin-filters.php';
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-admin-reports.php';
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-customer-reports.php';
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-contacts-page.php';
        require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-ppcart-extension-page.php';

        add_action('admin_notices', [$this,'admin_notices']);
        add_action('ppcart_settings_admin_notices', [$this,'admin_notices'], 10);
        add_action('ppcart_customer_report_admin_notices', [$this,'admin_notices'], 10);
        add_action('admin_init', [$this, 'maybe_dismiss_admin_notices']);
    }





    /**
     * Gets enabled payment processors currently running in test mode.
     *
     * @return array
     */
    /**
     * Adds a non-dismissible admin bar notice when payment processors use test mode.
     *
     * @param WP_Admin_Bar $wp_admin_bar WordPress admin bar instance.
     * @return void
     */
    /**
     * Prints admin bar styles for the test mode notice.
     *
     * @return void
     */
    /**
     * Starts buffering global admin notices so they can render inside the settings shell.
     *
     * @return void
     */
    /**
     * Stops buffering global admin notices for the settings shell.
     *
     * @return void
     */




















    //get_activecampaign_lists

    //get_activecampaign_tags
}
