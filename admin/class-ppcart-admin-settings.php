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
require_once __DIR__ . '/settings/class-ppcart-admin-stripe-connect-settings.php';
require_once __DIR__ . '/settings/traits/trait-ppcart-admin-settings-email.php';
require_once __DIR__ . '/settings/traits/trait-ppcart-admin-settings-screen.php';
require_once __DIR__ . '/settings/traits/trait-ppcart-admin-settings-sections.php';
require_once __DIR__ . '/settings/traits/trait-ppcart-admin-settings-core-options.php';
require_once __DIR__ . '/settings/traits/trait-ppcart-admin-settings-billing-options.php';
require_once __DIR__ . '/settings/traits/trait-ppcart-admin-settings-integration-options.php';
require_once __DIR__ . '/settings/traits/trait-ppcart-admin-settings-fields.php';

class PPCart_Admin_Settings
{
    use PPCart_Admin_Settings_Email_Trait;
    use PPCart_Admin_Settings_Screen_Trait;
    use PPCart_Admin_Settings_Sections_Trait;
    use PPCart_Admin_Settings_Core_Options_Trait;
    use PPCart_Admin_Settings_Billing_Options_Trait;
    use PPCart_Admin_Settings_Integration_Options_Trait;
    use PPCart_Admin_Settings_Fields_Trait;

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
     * Stripe Connect settings controller.
     *
     * @var PPCart_Admin_Stripe_Connect_Settings
     */
    private $stripe_connect;

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
        $this->stripe_connect = new PPCart_Admin_Stripe_Connect_Settings();

        add_filter('admin_body_class', [$this,'admin_body_class']);
        add_action('admin_footer', [$this, 'render_plugin_admin_footer']);
        add_action('wp_ajax_ppcart_reset_email_template', [ $this, 'reset_email_template' ]);
        add_action('wp_ajax_ppcart_preview_email_template', [ $this, 'preview_email_template' ]);
        add_action('wp_ajax_ppcart_preview_product_notification_email', [ $this, 'preview_product_notification_email' ]);
        add_action('wp_ajax_ppcart_send_product_notification_test', [ $this, 'send_product_notification_test' ]);
    }
}
