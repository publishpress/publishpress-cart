<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Order_Persistence
{
    public static function create($order)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-persistence-create.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function update($order)
    {
        if (function_exists('ppcart_preserve_stripe_owned_fields')) {
            $order = ppcart_preserve_stripe_owned_fields($order);
        }

        $keys = $order->attrs;
        foreach ($keys as $key) {
            if ($key == 'order_log') {
                continue;
            } elseif (isset($order->$key) && $order->$key) {
                ppcart_update_post_meta($order->id, $key, self::prepare_meta_value($key, $order->$key));
            } else {
                ppcart_delete_post_meta($order->id, $key);
            }
        }

        wp_update_post([ 'ID'   =>  $order->id, 'post_status'   =>  $order->status ]);
        self::store_items($order);
        do_action('ppcart_order_updated', $order);

        return $order->id;
    }

    private static function prepare_meta_value($key, $value)
    {
        // Flatten plan/order_bumps objects so unserialized meta never yields __PHP_Incomplete_Class.
        if (!in_array($key, ['plan', 'order_bumps'], true)) {
            return $value;
        }

        return self::flatten_objects($value);
    }

    private static function flatten_objects($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::flatten_objects($item);
            }

            return $value;
        }

        if (is_object($value)) {
            $normalized = new stdClass();

            foreach (get_object_vars($value) as $key => $item) {
                $normalized->$key = self::flatten_objects($item);
            }

            return $normalized;
        }

        return $value;
    }

    public function store($trigger_integrations = true)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-persistence-store.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function log($id, $status)
    {
        ppcart_log_entry($id, $status);
    }

    public function check_first_order()
    {
        $sub = new PPCart_Subscription($this->subscription_id);
        if ($sub->id && $sub->order_count() >= 1) {
            $first_order = $sub->first_order();
            if ($sub->order_count() == 1 && $first_order && !in_array($first_order->status, ['paid','refunded'])) {
                // first and only transaction was never updated to paid, make this the first order so that new order integrations can run
                $this->id = $first_order->id;
            }
            if ($this->id != $first_order->id) {
                $this->renewal = true;
            }
        }
    }

    public static function store_items($order)
    {

        if (!isset($order->items) || !is_countable($order->items)) {
            return false;
        }

        foreach ($order->items as $i) {
            $item = new PPCart_Order_Item();

            $i['order_id'] ??= $order->id;

            foreach ($i as $key => $val) {
                $item->$key = $val;
            }
            $item->store();
        }

        unset($order->items);
    }

    public function set_date_from_timestamp($gmt_timestamp)
    {
        $iso_date = gmdate('Y-m-d H:i:s', $gmt_timestamp);
        return $this->set_date($iso_date);
    }

    public function set_date($date_time)
    {
        if (!$this->id) {
            return false;
        }

        wp_update_post(
            [
            'ID' => $this->id,
            'post_date' => get_gmt_from_date($date_time),
            'post_date_gmt' => get_gmt_from_date($date_time),
            ],
            false
        );

        return get_gmt_from_date($date_time);
    }

    public function refund_log($amount, $refundID)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-persistence-refund-log.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
