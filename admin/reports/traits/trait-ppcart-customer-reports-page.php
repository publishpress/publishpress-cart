<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Customer_Reports_Page_Trait
{
    /**
     * This function introduces the plugin options into a top-level
     * 'CreativCart' menu.
     */
    public function setup_plugin_options_menu()
    {

        add_submenu_page(
            PPCart_Admin_Screens::menu_slug(),
            '',
            '',
            ppcart_live_cap('manager_option'),
            PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS,
            [$this, 'render_reports_page_content']
        );
    }

    /**
     * Renders a simple page to display for the theme menu defined above.
     */
    public function render_reports_page_content($active_tab = '')
    {
        include __DIR__ . '/../templates/customer-reports-page.php';
    }
}
