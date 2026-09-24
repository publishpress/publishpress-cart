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
 * The report-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 * @author PublishPress <help@publishpress.com>
 */
require_once __DIR__ . '/reports/traits/trait-ppcart-admin-reports-date.php';
require_once __DIR__ . '/reports/traits/trait-ppcart-admin-reports-data.php';
require_once __DIR__ . '/reports/traits/trait-ppcart-admin-reports-render.php';
require_once __DIR__ . '/reports/traits/trait-ppcart-admin-reports-page.php';
class PPCart_Admin_Reports
{
    use PPCart_Admin_Reports_Date_Trait;
    use PPCart_Admin_Reports_Data_Trait;
    use PPCart_Admin_Reports_Render_Trait;
    use PPCart_Admin_Reports_Page_Trait;

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
    }

    /**
     * This function introduces the plugin options into a top-level
     * 'CreativCart' menu.
     */
    public function setup_plugin_options_menu()
    {
        add_submenu_page(
            PPCart_Admin_Screens::menu_slug(),
            apply_filters($this->plugin_name . '-settings-page-title', esc_html__('Reports', 'publishpress-cart')),
            apply_filters($this->plugin_name . '-settings-menu-title', esc_html__('Reports', 'publishpress-cart')),
            ppcart_live_cap('manager_option'),
            PPCart_Admin_Screens::PAGE_REPORTS,
            [ $this, 'render_reports_page_content' ]
        );
    }
}
