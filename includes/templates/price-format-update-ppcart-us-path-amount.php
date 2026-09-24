<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;
$us_path_types = function_exists('ppcart_query_pro_post_types') ? ppcart_query_pro_post_types('us_path') : [ 'ppcart_us_path' ];
if (! $us_path_types) {
    $prepared_types = "''";
} else {
    $placeholders = implode(',', array_fill(0, count($us_path_types), '%s'));
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholder string is generated from the count of sanitized Pro post types.
    $prepared_types = $wpdb->prepare($placeholders, $us_path_types);
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-off migration query uses a prepared Pro post type list.
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $prepared_types is produced by $wpdb->prepare() from sanitized post type slugs.
$result = $wpdb->get_results(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type IN (" . $prepared_types . ")",
    ARRAY_A
);
// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
$usIds = array_column($result, 'ID');

if (!empty($usIds)) {
    foreach ($usIds as $us_id) {
        for ($i = 1; $i <= 5; $i++) {
            $usPrice = ppcart_get_post_meta($us_id, 'us_price_' . $i, true);
            if ($usPrice) {
                $amount = $this->check_price_format($usPrice);
                if ($amount) {
                    ppcart_update_post_meta($us_id, 'us_price_' . $i, $amount);
                }
            }

            $dsPrice = ppcart_get_post_meta($us_id, 'ds_price_' . $i, true);
            if ($dsPrice) {
                $amount = $this->check_price_format($dsPrice);
                if ($amount) {
                    ppcart_update_post_meta($us_id, 'ds_price_' . $i, $amount);
                }
            }
        }
    }
}
