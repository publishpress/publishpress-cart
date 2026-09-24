<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Debug_Log_Viewer_Render_Trait
{
    /**
     * Render one flat event row.
     *
     * @param array $row       Parsed row.
     * @param int   $row_index Row index.
     * @return void
     */
    private static function render_event_row($row, $row_index)
    {
        include __DIR__ . '/templates/debug-log-viewer-render-event-row.php';
    }

    /**
     * Render one grouped workflow row.
     *
     * @param array $group     Group data.
     * @param int   $row_index Row index.
     * @return void
     */
    private static function render_group_row($group, $row_index)
    {
        include __DIR__ . '/templates/debug-log-viewer-render-group-row.php';
    }

    /**
     * Render details panel for a grouped workflow.
     *
     * @param array $group Group data.
     * @return string
     */
    private static function render_group_details_panel($group)
    {
        return include __DIR__ . '/templates/debug-log-viewer-render-group-details-panel.php';
    }

    /**
     * Render details panel for one parsed debug entry.
     *
     * @param array $row Parsed row.
     * @return string
     */
    private static function render_details_panel($row)
    {
        return include __DIR__ . '/templates/debug-log-viewer-render-details-panel.php';
    }

    /**
     * Render one details label/value pair.
     *
     * @param string $label Label.
     * @param mixed  $value Value.
     * @param bool   $code  Whether to display as code.
     * @return string
     */
    private static function render_detail_item($label, $value, $code = false)
    {
        return include __DIR__ . '/templates/debug-log-viewer-render-detail-item.php';
    }

    /**
     * Render a status badge.
     *
     * @param string $level Debug level.
     * @return string
     */
    private static function render_level_badge($level)
    {
        return include __DIR__ . '/templates/debug-log-viewer-render-level-badge.php';
    }

    /**
     * Render a small workflow icon.
     *
     * @param string $workflow Workflow label.
     * @return string
     */
    private static function render_workflow_icon($workflow)
    {
        return include __DIR__ . '/templates/debug-log-viewer-render-workflow-icon.php';
    }

    /**
     * Enqueue assets for the standalone debug log viewer route.
     *
     * @return void
     */
    private static function enqueue_assets()
    {
        include __DIR__ . '/templates/debug-log-viewer-render-enqueue-assets.php';
    }

    /**
     * Render standalone viewer styles.
     *
     * @return void
     */
    private static function render_styles()
    {
        include __DIR__ . '/templates/debug-log-viewer-render-styles.php';
    }

    /**
     * Render details toggle script.
     *
     * @return void
     */
    private static function render_script()
    {
        include __DIR__ . '/templates/debug-log-viewer-render-script.php';
    }

    /**
     * Add a nonce to an admin URL.
     *
     * @param string $path       Admin path.
     * @param string $action     Nonce action.
     * @param string $nonce_name Nonce query arg.
     * @return string
     */
    private static function nonce_url($path, $action, $nonce_name)
    {
        return include __DIR__ . '/templates/debug-log-viewer-render-nonce-url.php';
    }
}
