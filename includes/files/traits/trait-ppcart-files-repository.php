<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Files_Repository_Trait
{
    public function get_product_files($id)
    {
        return ppcart_get_post_meta($id, 'files', true);
    }

    public function get_product_file($prod_id, $file_id)
    {
        if ($files = $this->get_product_files($prod_id)) {
            foreach ($files as $file) {
                if ($file['file_id'] == $file_id) {
                    return $file;
                }
            }
        }
        return false;
    }

    public function get_order_downloads($order_id = 0, $args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-repository-get-order-downloads.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function log_download($file)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-repository-log-download.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_download_by_key($key = 0, $args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-repository-get-download-by-key.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function revoke_access($id)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Deletes a single download access row by ID.
        return $wpdb->delete(self::live_table(), [ 'download_id' => $id ], [ '%d' ]);
    }

    public function setup_download($download, $show_hidden = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-repository-setup-download.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
