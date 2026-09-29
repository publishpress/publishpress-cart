<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;
$us_path_types = function_exists('ppcart_query_pro_post_types') ? ppcart_query_pro_post_types('us_path') : [ 'ppcart_us_path' ];
if (! $us_path_types) {
    $us_path_types = [ '' ];
}
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration query.
$result = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type IN (" . implode(',', array_fill(0, count($us_path_types), '%s')) . ')',
        array_values($us_path_types)
    ),
    ARRAY_A
);
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
