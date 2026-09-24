<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Customer_Trait
{
    private function get_cached_stripe_customer_id($email, $gateway_mode)
    {
        $email = strtolower(sanitize_email($email));

        if (! is_email($email)) {
            return '';
        }

        $meta_query = [
            'relation' => 'AND',
            [
                'key' => ppcart_meta_key('email'),
                'value' => $email,
            ],
            [
                'key' => ppcart_meta_key('customer_id'),
                'compare' => 'EXISTS',
            ],
        ];

        if (! empty($gateway_mode)) {
            $meta_query[] = [
                'key' => ppcart_meta_key('gateway_mode'),
                'value' => sanitize_text_field($gateway_mode),
            ];
        }

        $customer_posts = get_posts(
            [
                'post_type'              => array_merge(ppcart_query_post_types('order'), ppcart_query_post_types('subscription')),
                'post_status'            => 'any',
                'posts_per_page'         => 5,
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to reuse a previously stored Stripe customer during checkout.
                'meta_query'             => $meta_query,
            ]
        );

        foreach ($customer_posts as $post_id) {
            $customer_id = ppcart_get_post_meta($post_id, 'customer_id', true);

            if (is_string($customer_id) && preg_match('/^cus_[A-Za-z0-9]+$/', $customer_id)) {
                return $customer_id;
            }
        }

        return '';
    }

    private function get_or_create_stripe_customer($stripe, $customer_args, $email)
    {
        $customer = $stripe->customers->all(
            [
                'email' => $email,
                'limit' => 1,
            ]
        );

        if (! empty($customer->data)) {
            return $customer->data[0];
        }

        return $stripe->customers->create($customer_args);
    }

    private function is_missing_stripe_customer_exception($exception)
    {
        if (! is_a($exception, 'PublishPress\\Stripe\\Exception\\InvalidRequestException')) {
            return false;
        }

        if (false !== stripos($exception->getMessage(), 'No such customer')) {
            return true;
        }

        $stripe_code = method_exists($exception, 'getStripeCode') ? $exception->getStripeCode() : '';
        if ('resource_missing' !== $stripe_code) {
            return false;
        }

        $stripe_param = method_exists($exception, 'getStripeParam') ? $exception->getStripeParam() : '';
        if ('customer' === $stripe_param) {
            return true;
        }

        $error = method_exists($exception, 'getError') ? $exception->getError() : null;
        $param = (is_object($error) && isset($error->param)) ? $error->param : '';

        return 'customer' === $param;
    }

    private function is_missing_stripe_resource_exception($exception)
    {
        if (is_a($exception, 'PublishPress\\Stripe\\Exception\\InvalidRequestException')) {
            $stripe_code = method_exists($exception, 'getStripeCode') ? $exception->getStripeCode() : '';
            if ('resource_missing' === $stripe_code) {
                return true;
            }
        }

        return false !== stripos($exception->getMessage(), 'No such payment_intent');
    }
}
