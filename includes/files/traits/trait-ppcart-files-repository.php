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

    /**
     * Raw native access records for internal authorization and checked administration.
     *
     * @since 1.1.0
     * @param int $order_id Order.
     * @param array $args Native query options.
     * @return array|false
     */
    public function get_download_access_records($order_id, $args = [])
    {
        return include __DIR__ . '/templates/files-repository-get-access-records.php';
    }

    /**
     * Change native access counters/expiry with optional optimistic concurrency.
     *
     * @since 1.1.0
     * @param int $order_id Related order.
     * @param int $download_id Access row.
     * @param array $changes Reviewed counter/expiry fields.
     * @param array $expected Prior native values.
     * @return int|false
     */
    public function update_download_access($order_id, $download_id, array $changes, array $expected = [])
    {
        if (! current_user_can('edit_post', $order_id) || ! $changes
            || array_diff(array_keys($changes), ['downloads_remaining', 'download_expires'])) {
            return false;
        }
        global $wpdb;
        $where = ['order_id' => $order_id, 'download_id' => $download_id]
            + array_intersect_key($expected, array_flip(['downloads_remaining', 'download_expires', 'downloads']));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed native table/columns with checked IDs and optional prior values.
        return $wpdb->update(self::live_table(), $changes, $where);
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
