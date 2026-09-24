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
 * The Customer-report-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the Customer-specific stylesheet and JavaScript.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 * @author PublishPress <help@publishpress.com>
 */
require_once __DIR__ . '/reports/traits/trait-ppcart-customer-reports-page.php';

class PPCart_Customer_Reports
{
    use PPCart_Customer_Reports_Page_Trait;


    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The Nice Name of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_title    The Nice Name of this plugin.
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
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $plugin_title, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;

        add_action('admin_head', function () {
            PPCart_Admin_Screens::remove_submenu_page(PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS);
        });
    }
}
