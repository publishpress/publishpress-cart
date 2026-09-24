<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Files_Repository_Trait::get_order_downloads().


global $wpdb;

if (!$order_id) {
    return false;
}

$defaults = [
    'status' => 'active',
];
$args = wp_parse_args($args, $defaults);

$order_status = ppcart_get_post_meta($order_id, 'status', true);
if ($args['status'] != 'all' && !in_array($order_status, ['paid', 'complete'])) {
    return false;
}

$now = ppcart_localize_dt()->format('Y-m-d H:i:s');

$table_name = self::live_table();

if ($args['status'] == 'active' && isset($args['product_id'])) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query reads plugin-owned download table.
    $order_downloads = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE order_id = %d AND (download_expires IS NULL OR download_expires > STR_TO_DATE(%s, %s)) AND (downloads_remaining = 'unlimited' OR downloads IS NULL OR downloads_remaining > 0) AND product_id = %d",
            $table_name,
            $order_id,
            $now,
            '%Y-%m-%d %H:%i:%s',
            (int) $args['product_id']
        )
    );
} elseif ($args['status'] == 'active') {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query reads plugin-owned download table.
    $order_downloads = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE order_id = %d AND (download_expires IS NULL OR download_expires > STR_TO_DATE(%s, %s)) AND (downloads_remaining = 'unlimited' OR downloads IS NULL OR downloads_remaining > 0)",
            $table_name,
            $order_id,
            $now,
            '%Y-%m-%d %H:%i:%s'
        )
    );
} elseif (isset($args['product_id'])) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query reads plugin-owned download table.
    $order_downloads = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT * FROM %i WHERE order_id = %d AND product_id = %d',
            $table_name,
            $order_id,
            (int) $args['product_id']
        )
    );
} else {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query reads plugin-owned download table.
    $order_downloads = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT * FROM %i WHERE order_id = %d',
            $table_name,
            $order_id
        )
    );
}

if ($order_downloads) {
    $downloads = [];
    foreach ($order_downloads as $download) {
        $show_hidden = ($args['status'] == 'all') ? true : false;
        if ($download = $this->setup_download($download, $show_hidden)) {
            $downloads[] = $download;
        }
    }
    return $downloads;
}

return false;
