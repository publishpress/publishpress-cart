<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once plugin_dir_path(__FILE__) . 'class-ppcart-dashboard-data.php';
require_once plugin_dir_path(__FILE__) . 'class-ppcart-dashboard-renderer.php';

/**
 * Registers the WordPress dashboard monthly overview widget.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Dashboard_Widget
{
    private $data;

    private $renderer;

    public function __construct()
    {
        $this->data     = new PPCart_Dashboard_Data();
        $this->renderer = new PPCart_Dashboard_Renderer();

        add_action('wp_dashboard_setup', [$this, 'register']);
    }

    public function register()
    {
        wp_add_dashboard_widget(
            'ppcart_dashboard_widget',
            /* translators: %s: plugin title. */
            sprintf(__('Monthly Overview for %s', 'publishpress-cart'), apply_filters('ppcart_plugin_title', 'PublishPress Cart')),
            [$this, 'render']
        );
    }

    public function render()
    {
        $this->renderer->render($this->data->get_summary());
    }
}

new PPCart_Dashboard_Widget();
