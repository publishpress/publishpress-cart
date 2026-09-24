<?php

if (! defined('ABSPATH')) {
    exit;
}


$cart_order = class_exists('PPCart_Order') ? new PPCart_Order($order_id) : null;

if (empty($context['amount'])) {
    $context['amount'] = self::get_record_value($cart_order, $order_id, 'amount', 'amount');
}

if (empty($context['currency'])) {
    $context['currency'] = self::get_record_value($cart_order, $order_id, 'currency', 'currency');
}

if (empty($context['subscription_id'])) {
    $subscription_id = self::get_record_value($cart_order, $order_id, 'subscription_id', 'subscription_id');
    if ($subscription_id) {
        $context['subscription_id'] = $subscription_id;
    }
}

return self::hydrate_customer_context($cart_order, $order_id, $context);
