<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in PPCart_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The PPCart_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

global $ppcart_is_admin_screen;

if (! PPCart_Admin_Screens::is_plugin_screen($hook_suffix)) {
    return;
}

$ppcart_is_admin_screen = true;

wp_enqueue_style('ppcart', PPCART_BASE_URL . 'admin/css/ppcart-admin.css', [], $this->version, 'all');
wp_enqueue_style('ppcart-pro-locks', PPCART_BASE_URL . 'admin/css/ppcart-pro-locks.css', [ 'ppcart' ], $this->version, 'all');
wp_enqueue_style('ppcart-selectize-default', PPCART_BASE_URL . 'admin/css/selectize.default.css', [], $this->version, 'all');
wp_enqueue_style('ppcart-daterangepicker', PPCART_BASE_URL . 'admin/css/ppcart-daterangepicker.min.css', [], $this->version, 'all');
wp_enqueue_style('ppcart-balloon', PPCART_BASE_URL . 'admin/assets/libs/balloon.min.css', [], $this->version, 'all');
wp_enqueue_style('ppcart-flatpickr', PPCART_BASE_URL . 'admin/assets/libs/flatpickr.min.css', [], $this->version, 'all');
wp_enqueue_style('ppcart-datatables', PPCART_BASE_URL . 'admin/css/jquery.dataTables.min.css', [], $this->version, 'all');
wp_enqueue_style('ppcart-font-awesome-svg-with-js', PPCART_BASE_URL . 'includes/assets/font-awesome-svg-with-js.min.css', [], $this->version, 'all');
wp_enqueue_style('wp-color-picker');

if (PPCart_Admin_Screens::is_plugin_screen($hook_suffix)) {
    wp_enqueue_style('wp-jquery-ui-dialog');
}

// Modern settings UI - only on the dedicated settings page.
if (PPCart_Admin_Screens::is_settings_screen($hook_suffix)) {
    wp_enqueue_style(
        'ppcart-settings',
        PPCART_BASE_URL . 'admin/css/ppcart-settings.css',
        [ 'ppcart' ],
        $this->version,
        'all'
    );
}
if (PPCart_Admin_Screens::is_modern_admin_ui_screen($hook_suffix)) {
    wp_enqueue_style(
        'ppcart-orders',
        PPCART_BASE_URL . 'admin/css/ppcart-orders.css',
        [ 'ppcart' ],
        $this->version,
        'all'
    );
}
if (PPCart_Admin_Screens::is_reports_screen($hook_suffix)) {
    wp_enqueue_style(
        'ppcart-reports',
        PPCART_BASE_URL . 'admin/css/ppcart-reports.css',
        [ 'ppcart', 'ppcart-flatpickr' ],
        $this->version,
        'all'
    );
}
wp_add_inline_style(
    'ppcart',
    '.pp-advertisement-right-sidebar .advertisement-box-header{background:#655897;color:#fff;}.pp-advertisement-right-sidebar .advertisement-box-content{border:1px solid #655897;}.pp-advertisement-right-sidebar a.advert-link{display:block;margin-top:10px;font-size:1em;}.pp-advertisement-right-sidebar .linkIcon{width:16px;height:16px;vertical-align:middle;margin-left:4px;fill:currentColor;}.pp-advertisement-right-sidebar h3.hndle{font-size:14px;padding:8px 12px;margin:0;line-height:1.4;}'
);
