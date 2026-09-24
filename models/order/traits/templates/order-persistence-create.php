<?php

if (! defined('ABSPATH')) {
    exit;
}


// create order

$order_post_id = wp_insert_post(['post_title' => $order->product_name, 'post_type' => ppcart_live_post_type('order'), 'post_status' => $order->status], false);

wp_update_post(
    [
    'ID' => $order_post_id,
    'post_title' => "#" . $order_post_id . " " . $order->customer_name,
    ],
    false
);

$keys = $order->attrs;
foreach ($keys as $key) {
    if (isset($order->$key) && $order->$key) {
        update_post_meta($order_post_id, ppcart_meta_key($key), self::prepare_meta_value($key, $order->$key));
    }
}

self::ensure_invoice_access_token($order_post_id);

if ($order->order_parent > 0) {
    add_post_meta($order->order_parent, ppcart_meta_key('order_child'), ['id' => $order_post_id, 'type' => $order->order_type]);
}

$order->id = $order_post_id;

self::store_items($order);
do_action('ppcart_order_created', $order);

return $order_post_id;
