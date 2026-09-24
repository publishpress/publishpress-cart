<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Order_Query_Trait
{
    public function order_related_orders_filter($query)
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return $query;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter query parameter.
        $subscription_related_orders = isset($_GET['subscription_related_orders']) ? sanitize_text_field(wp_unslash($_GET['subscription_related_orders'])) : '';
        if ('' === $subscription_related_orders) {
            return $query;
        }

        $custom_meta = [
            'key' => ppcart_meta_key('subscription_id'),
            'value' => $subscription_related_orders,
            'compare' => '==',
        ];
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to constrain admin orders list to a subscription.
        $query->set('meta_query', [$custom_meta]);

        return $query;
    }

    public function order_email_filter($query)
    {

        if (! is_admin() || ! $query->is_main_query()) {
            return $query;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter query parameter.
        $order_email = isset($_GET['order_email']) ? sanitize_text_field(wp_unslash($_GET['order_email'])) : '';
        if ('' === $order_email) {
            return $query;
        }

        $custom_meta = [
            'key' => ppcart_meta_key('email'),
            'value' => $order_email,
            'compare' => '==',
        ];
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to constrain admin orders list to customer email.
        $query->set('meta_query', [$custom_meta]);

        return $query;
    }
}
