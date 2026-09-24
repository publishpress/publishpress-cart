<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Maintenance settings UI and AJAX handlers.
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
        add_action('wp_ajax_ppcart_db_schema_status', [ $this, 'ajax_db_schema_status' ]);
        add_action('wp_ajax_ppcart_fix_db_schema', [ $this, 'ajax_fix_db_schema' ]);
    }

    /**
     * Returns the Maintenance panel markup; the check runs only when the tab is opened.
     *
     * @return void
     */
    public function ajax_db_schema_status()
    {
        ppcart_check_ajax_referer('ppcart_db_schema_status', 'nonce');

        $this->require_manage_options(__('You do not have permission to check the database schema.', 'publishpress-cart'));

        wp_send_json_success(
            [ 'html' => wp_kses($this->render_maintenance_html(), ppcart_admin_allowed_html()) ]
        );
    }

    /**
     * @return void
     */
    public function ajax_fix_db_schema()
    {
        ppcart_check_ajax_referer('ppcart_fix_db_schema', 'nonce');

        $this->require_manage_options(__('You do not have permission to repair the database schema.', 'publishpress-cart'));

        $report = $this->service->check_all();

        if ($report->is_healthy()) {
            wp_send_json_error(
                [ 'message' => __('The database schema already passed. There is nothing to repair.', 'publishpress-cart') ],
                409
            );
        }

        $report = $this->service->repair($report);

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
     * Placeholder rendered on every Settings load; runs no schema queries.
     *
     * @return string
     */
    public function render_maintenance_placeholder_html()
    {
        return sprintf(
            '<div class="ppcart-db-schema-maintenance" data-ppcart-db-schema-panel data-nonce="%1$s"><p class="description">%2$s</p></div>',
            esc_attr(wp_create_nonce('ppcart_db_schema_status')),
            esc_html__('Checking the database schema…', 'publishpress-cart')
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

    /**
     * @param string $message Error message for users without manage_options.
     * @return void
     */
    private function require_manage_options($message)
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error([ 'message' => $message ], 403);
        }
    }
}
