<?php

if (! defined('ABSPATH')) {
    exit;
}


$order_id = absint(self::metadata($charge, 'ppcart_order_id', 0));
if ($order_id) {
    $cart_order = new PPCart_Order($order_id);
    $payment_ids = array_filter([ (string) self::get($charge, 'id', ''), (string) self::get($charge, 'payment_intent', '') ]);
    if ($cart_order->id && (! ppcart_order_is_shopping_cart($cart_order) || in_array((string) $cart_order->transaction_id, $payment_ids, true))) {
        return $cart_order;
    }
}

$cart_order = PPCart_Order::get_by_trans_id(self::get($charge, 'id', ''));
if (! $cart_order && self::get($charge, 'payment_intent', '')) {
    $cart_order = PPCart_Order::get_by_trans_id(self::get($charge, 'payment_intent', ''));
}

return $cart_order;
