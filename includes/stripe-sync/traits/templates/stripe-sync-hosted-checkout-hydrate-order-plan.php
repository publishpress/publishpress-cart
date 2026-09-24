<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $order || is_object($order->plan)) {
    return;
}

if (is_string($order->plan) && '' !== $order->plan) {
    $maybe_plan = @unserialize($order->plan); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Rehydrating a first-party plan object stored by this plugin.
    if (is_object($maybe_plan)) {
        $order->plan = $maybe_plan;
        return;
    }
}

if (function_exists('ppcart_plan') && ! empty($order->option_id)) {
    $plan = ppcart_plan($order->option_id, $order->on_sale, $order->product_id);
    if (is_object($plan)) {
        $order->plan = $plan;
    }
}
