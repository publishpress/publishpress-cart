<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb, $ppcart_debug_logger;

$args = [];
foreach ($this->cols as $col) {
    $args[$col] = $this->$col;
}

$ppcart_debug_logger->log_event(
    'order.item.creating',
    'Creating order item for checkout order.',
    [
        'order_id'   => isset($args['order_id']) ? (int) $args['order_id'] : 0,
        'product_id' => isset($args['product_id']) ? (int) $args['product_id'] : 0,
        'item_type'  => $args['item_type'] ?? '',
        'amount'     => $args['total_amount'] ?? '',
    ]
);

$wpdb->insert(ppcart_live_table('order_items'), $args); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write to plugin custom table.

if ($this->id = $wpdb->insert_id) {
    $ppcart_debug_logger->log_event(
        'order.item.created',
        "Order item #{$this->id} created.",
        [
            'order_id'   => isset($args['order_id']) ? (int) $args['order_id'] : 0,
            'product_id' => isset($args['product_id']) ? (int) $args['product_id'] : 0,
            'item_id'    => (int) $this->id,
        ],
        0
    );
    foreach ($this->meta as $key) {
        if (isset($this->$key) && $this->$key) {
            $this->update_meta($key, $this->$key);
        }
    }
} else {
    $ppcart_debug_logger->log_debug('wpdb last query: ' . wp_json_encode($wpdb->last_query));
    $ppcart_debug_logger->log_debug('wpdb last result: ' . wp_json_encode($wpdb->last_result));
    $ppcart_debug_logger->log_debug('wpdb last error: ' . wp_json_encode($wpdb->last_error));
}
