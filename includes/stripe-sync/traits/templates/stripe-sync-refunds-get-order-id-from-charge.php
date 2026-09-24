<?php

if (! defined('ABSPATH')) {
    exit;
}


$cart_order = self::find_order_for_charge($charge);
return ($cart_order && $cart_order->id) ? $cart_order->id : 0;
