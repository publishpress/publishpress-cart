<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;

if (!$order_id) {
    return false;
}

$items = [];

$obj = new PPCart_Order_Item();

$table_name = ppcart_live_table('order_items');

$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read from plugin custom table.
    $wpdb->prepare('SELECT order_item_id FROM %i WHERE order_id = %d', $table_name, $order_id)
);
if (is_countable($results)) {
    foreach ($results as $res) {
        $item = new PPCart_Order_Item($res->order_item_id);
        if ($item->id) {
            $items[] = $item;
        }
    }
}
return $items;
