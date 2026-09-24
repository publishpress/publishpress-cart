<?php

if (! defined('ABSPATH')) {
    exit;
}


$order_id = absint(self::metadata($charge, 'ppcart_order_id', 0));
if ($order_id) {
    $cart_order = new PPCart_Order($order_id);
    if ($cart_order->id) {
        return $cart_order;
    }
}

$cart_order = PPCart_Order::get_by_trans_id(self::get($charge, 'id', ''));
if (! $cart_order && self::get($charge, 'payment_intent', '')) {
    $cart_order = PPCart_Order::get_by_trans_id(self::get($charge, 'payment_intent', ''));
}

return $cart_order;
