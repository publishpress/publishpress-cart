<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Files_Repository_Trait::get_download_by_key().

global $wpdb;

$defaults = [
    'status' => 'active',
];
$args = wp_parse_args($args, $defaults);

$table_name = self::live_table();

$now = ppcart_localize_dt()->format('Y-m-d H:i:s');

if ($args['status'] == 'active') {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query reads plugin-owned download table.
    $res = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE order_key = %s AND (download_expires IS NULL OR download_expires > STR_TO_DATE(%s, %s)) AND (downloads_remaining = 'unlimited' OR downloads IS NULL OR downloads_remaining > 0) LIMIT 1",
            $table_name,
            $key,
            $now,
            '%Y-%m-%d %H:%i:%s'
        )
    );
} else {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query reads plugin-owned download table.
    $res = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT * FROM %i WHERE order_key = %s LIMIT 1',
            $table_name,
            $key
        )
    );
}

if ($res) {
    return $this->setup_download($res[0]);
}

return false;
