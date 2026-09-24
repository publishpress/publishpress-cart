<?php

if (! defined('ABSPATH')) {
    exit;
}


$record_ids = [
    'order_id'        => self::context_int($context, [ 'order_id', 'ppcart_order_id', 'record_id' ]),
    'subscription_id' => self::context_int($context, [ 'subscription_id', 'ppcart_subscription_id' ]),
    'product_id'      => self::context_int($context, [ 'product_id', 'ppcart_product_id' ]),
];

if (! $record_ids['order_id'] && preg_match('/PPCart_Order[^\d]*(\d+)/i', $message, $matches)) {
    $record_ids['order_id'] = absint($matches[1]);
}

if (! $record_ids['order_id'] && preg_match('/\border\s+(?:#|id:?|ID:?)?\s*(\d+)/i', $message, $matches)) {
    $record_ids['order_id'] = absint($matches[1]);
}

if (! $record_ids['subscription_id'] && preg_match('/PPCart_Subscription[^\d]*(\d+)/i', $message, $matches)) {
    $record_ids['subscription_id'] = absint($matches[1]);
}

if (! $record_ids['subscription_id'] && preg_match('/\bsubscription\s+(?:#|id:?|ID:?)?\s*(\d+)/i', $message, $matches)) {
    $record_ids['subscription_id'] = absint($matches[1]);
}

if (! $record_ids['product_id'] && preg_match('/\bproduct\s+(?:#|id:?|ID:?)?\s*(\d+)/i', $message, $matches)) {
    $record_ids['product_id'] = absint($matches[1]);
}

return $record_ids;
