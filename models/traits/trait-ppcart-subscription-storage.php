<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Subscription_Storage
{
    public function initialize($defaults, $order_defaults, $obj = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-storage-initialize.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function create($sub)
    {
        // create order
        $post_id = wp_insert_post(['post_title' => $sub->product_name, 'post_type' => ppcart_live_post_type('subscription'), 'post_status' => $sub->status], false);

        wp_update_post(
            [
            'ID' => $post_id,
            'post_title' => "#" . $post_id . " " . $sub->customer_name,
            ],
            false
        );

        $keys = $sub->attrs;
        foreach ($keys as $key) {
            if (isset($sub->$key) && $sub->$key) {
                update_post_meta($post_id, ppcart_meta_key($key), $sub->$key);
            }
        }
        do_action('ppcart_subscription_created', $sub);
        return $post_id;
    }

    public static function update($sub)
    {
        if (! ppcart_is_subscription_post_type(get_post_type($sub->id))) {
            return false;
        }
        if (function_exists('ppcart_preserve_stripe_owned_fields')) {
            $sub = ppcart_preserve_stripe_owned_fields($sub);
        }
        $keys = $sub->attrs;
        foreach ($keys as $key) {
            if (isset($sub->$key) && $sub->$key) {
                update_post_meta($sub->id, ppcart_meta_key($key), $sub->$key);
            }
        }
        wp_update_post([ 'ID'   =>  $sub->id, 'post_status'   =>  $sub->status ]);
        do_action('ppcart_subscription_updated', $sub);
        return $sub->id;
    }

    public function store($trigger_integrations = true)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-storage-store.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function from_order($order = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-storage-from-order.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function cancel_at($time = 'period_end')
    {

        if ($time == 'period_end') {
            $time = $this->sub_next_bill_date;
        }

        if (!is_numeric($time)) {
            $time = new DateTime($time);
            $time = $time->format('U');
        }

        if (is_numeric($time)) {
            wp_schedule_single_event($time, 'ppcart_cancel_subscription_event', [$this]);
        }
    }

    public static function get_by_sub_id($sub_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-storage-get-by-sub-id.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
