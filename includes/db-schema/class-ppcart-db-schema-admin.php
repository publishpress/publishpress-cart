<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Maintenance settings UI and AJAX repair handler.
 */
final class PPCart_DB_Schema_Admin
{
    /** @var PPCart_DB_Schema_Service */
    private $service;

    /**
     * @param PPCart_DB_Schema_Service $service Schema service.
     */
    public function __construct(PPCart_DB_Schema_Service $service)
    {
        $this->service = $service;
    }

    /**
     * @return void
     */
    public function register()
    {
        add_action('wp_ajax_ppcart_fix_db_schema', [ $this, 'ajax_fix_db_schema' ]);
    }

    /**
     * @return void
     */
    public function ajax_fix_db_schema()
    {
        ppcart_check_ajax_referer('ppcart_fix_db_schema', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(
                [ 'message' => __('You do not have permission to repair the database schema.', 'publishpress-cart') ],
                403
            );
        }

        $report = $this->service->repair_all();

        wp_send_json_success(
            [
                'message' => $report->is_healthy()
                    ? __('Database schema repair completed successfully.', 'publishpress-cart')
                    : __('Database schema repair finished with remaining issues.', 'publishpress-cart'),
                'report'  => $report->to_array(),
            ]
        );
    }

    /**
     * @return string
     */
    public function render_maintenance_html()
    {
        $report = $this->service->check_all();
        $data   = $report->to_array();
        $nonce  = wp_create_nonce('ppcart_fix_db_schema');

        $__ppcart_template_result = include __DIR__ . '/templates/db-schema-maintenance.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
