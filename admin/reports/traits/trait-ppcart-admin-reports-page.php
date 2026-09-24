<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Reports_Page_Trait
{
    /**
     * Renders a simple page to display for the theme menu defined above.
     */
    public function render_reports_page_content($active_tab = '')
    {
        include __DIR__ . '/../templates/admin-reports-page.php';
    }
}
