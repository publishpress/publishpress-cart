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
 * The admin-ajax functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 *
 *
 * @package PPCart
 * @subpackage PPCart/admin
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Admin_Ajax
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

    public function __call($name, $arguments)
    {
        return $response = [
            'response' => ['message' => 'Invalid Method'],
            'response_code' => '401',
        ];
    }

    public function ajax_action()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-admin-ajax-ppcart-ajax-action.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Read the admin AJAX request by field before dispatching it to handlers.
     *
     * Only the dispatcher's own fields are expected here. A handler that needs more
     * declares them through ppcart_expected_request_fields on the 'admin_ajax:<action>'
     * context, so undeclared request keys never reach it.
     *
     * @param array  $data   Request data.
     * @param string $action Sanitized ppcart_action value.
     * @return array
     */
    private function sanitize_ajax_request_data($data, $action = '')
    {
        return ppcart_parse_admin_ajax_request($data, $action);
    }
}
