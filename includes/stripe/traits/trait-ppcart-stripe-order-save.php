<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Stripe_Order_Save_Trait
{
    public function find_stripe_webhook_order($order_info)
    {
        $ppcart_amount = ppcart_filter_input(INPUT_POST, 'ppcart_amount', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $ppcart_amount = is_string($ppcart_amount) ? (float) $ppcart_amount : 0;
        if ('stripe' === $order_info['pay_method'] && $ppcart_amount > 0) {
            $args = [
                'post_type'  => ppcart_query_post_types('order'),
                'post_status' => 'paid',
                'posts_per_page' => 1,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required lookup by saved Stripe intent ID.
                'meta_query' => [
                    [
                        'key' => ppcart_meta_key('intent_id'),
                        'value' => $order_info['intent_id'],
                    ],
                ],
            ];

            $posts = get_posts($args);
            if (!empty($posts)) {
                return $posts[0]->ID;
            }
        }
        return false;
    }

    public function do_stripe_order_save($order_info)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-order-save-do-stripe-order-save.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function is_stripe_order_complete($is_complete, $post_id)
    {
        if (ppcart_get_post_meta($post_id, 'transaction_id', true)) {
            return 'paid';
        }
        return $is_complete;
    }

    public function do_order_save($order_info, $status = 'pending-payment')
    {
        return include __DIR__ . '/../templates/order-save.php';
    }

    public function maybe_do_order_complete($post_id, $order_info, $status = false)
    {

        $status = apply_filters('ppcart_is_order_complete', $status, $post_id);

        if ($status) {
            $order_info['ID'] = $post_id;
            ppcart_trigger_integrations($status, $order_info);
        }

        return $post_id;
    }

    public function maybe_store_connect_fee_meta($post_id, $intent_id = '', $amount_for_stripe = 0, $currency = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-order-save-maybe-store-connect-fee-meta.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
