<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Debug_Log_Viewer_Page_Trait
{
    /**
     * Render the standalone modern debug log page.
     *
     * @param string $file_name Optional log file name.
     * @return void
     */
    public static function render_log_page($file_name = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-page-render-log-page.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Render the grouped master/detail debug log view.
     *
     * @param array    $groups        Paginated groups.
     * @param int      $showing_from  First visible row number.
     * @param int      $showing_to    Last visible row number.
     * @param int      $total         Total matching groups.
     * @param int      $page          Current page.
     * @param int      $total_pages   Total page count.
     * @param callable $build_page_url Pagination URL builder.
     * @return void
     */
    private static function render_grouped_split_view($groups, $showing_from, $showing_to, $total, $page, $total_pages, $build_page_url)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-page-render-grouped-split-view.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Render one right-side inspector panel.
     *
     * @param array  $panel       Panel entry data.
     * @param bool   $active      Whether panel is initially active.
     * @param string $previous_id Previous event ID.
     * @param string $next_id     Next event ID.
     * @return string
     */
    private static function render_inspector_panel($panel, $active, $previous_id, $next_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-page-render-inspector-panel.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Render pagination footer for grouped and raw views.
     *
     * @param int      $showing_from  First visible row number.
     * @param int      $showing_to    Last visible row number.
     * @param int      $total         Total matching rows.
     * @param string   $item_label    Item label.
     * @param int      $page          Current page.
     * @param int      $total_pages   Total page count.
     * @param callable $build_page_url Pagination URL builder.
     * @return void
     */
    private static function render_pagination_footer($showing_from, $showing_to, $total, $item_label, $page, $total_pages, $build_page_url)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-page-render-pagination-footer.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
