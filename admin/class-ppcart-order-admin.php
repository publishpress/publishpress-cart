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
class PPCart_Order_Admin
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
     * The prefix of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $prefix    The current version of this plugin.
     */
    private $prefix;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version, $prefix)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->prefix = $prefix;
    }

    public function save_post_order($post_id, $post)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-admin-save-post-ppcart-order.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
