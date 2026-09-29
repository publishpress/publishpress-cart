<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Monthly installment-plan collected and expected amounts for the dashboard widget.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Dashboard_Plan_Data
{
    public function get_summary()
    {
        $plans_query = $this->get_plans_query();
        $collected   = $this->get_collected_amount();

        return [
            'active_plans'  => $plans_query->found_posts,
            'collected'     => ppcart_format_price($collected),
            'expected'      => $this->get_expected_amount($plans_query, $collected),
            'cancellations' => $this->get_cancellation_count(),
        ];
    }

    private function get_plans_query()
    {
        return new WP_Query([
            'post_type'      => ppcart_query_post_types('subscription'),
            'post_status'    => 'active',
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Dashboard metrics require filtering installment plans.
            'meta_query'     => [
                [
                    'key' => ppcart_meta_key('sub_installments'),
                    'value'   => '1',
                    'compare' => '>=',
                ],
            ],
        ]);
    }

    private function get_expected_amount($installment_plans, $collected)
    {
        $total_revenue = 0;

        if ($installment_plans->have_posts()) {
            while ($installment_plans->have_posts()) {
                $installment_plans->the_post();
                $total_revenue += $this->get_plan_expected_revenue(get_the_ID());
            }
        }

        wp_reset_postdata();

        return ppcart_format_price($total_revenue - $collected);
    }

    private function get_plan_expected_revenue($subscription_id)
    {
        $total_installments = (int) ppcart_get_post_meta($subscription_id, 'sub_installments', true);
        $installment_amount = (float) ppcart_get_post_meta($subscription_id, 'sub_amount', true);

        return $total_installments * $installment_amount;
    }

    private function get_cancellation_count()
    {
        $query = new WP_Query([
            'post_type'      => ppcart_query_post_types('subscription'),
            'post_status'    => 'canceled',
            'date_query'     => [
                'after' => wp_date('Y-m-01'),
            ],
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Dashboard count requires filtering installment plans.
            'meta_query'     => [
                [
                    'key' => ppcart_meta_key('sub_installments'),
                    'value'   => '1',
                    'compare' => '>=',
                ],
            ],
        ]);

        return $query->found_posts;
    }

    private function get_collected_amount()
    {
        global $wpdb;

        $subscription_keys = ppcart_query_meta_keys('subscription_id');
        $order_types       = ppcart_query_post_types('order');
        $amount_keys       = ppcart_query_meta_keys('amount');
        $paid_statuses     = ppcart_query_post_statuses('paid');
        $installment_keys  = ppcart_query_meta_keys('sub_installments');
        $active_statuses   = ppcart_query_post_statuses('active');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dashboard widget total.
        $total_amount = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(order_amount.meta_value)
                FROM {$wpdb->posts} AS orders
                JOIN {$wpdb->postmeta} AS subscription_id ON orders.ID = subscription_id.post_id
                JOIN {$wpdb->postmeta} AS order_amount ON orders.ID = order_amount.post_id
                WHERE subscription_id.meta_key IN (" . implode(',', array_fill(0, count($subscription_keys), '%s')) . ")
                AND orders.post_type IN (" . implode(',', array_fill(0, count($order_types), '%s')) . ")
                AND order_amount.meta_key IN (" . implode(',', array_fill(0, count($amount_keys), '%s')) . ")
                AND orders.post_status IN (" . implode(',', array_fill(0, count($paid_statuses), '%s')) . ")
                AND orders.post_date >= %s
                AND subscription_id.meta_value IN (
                    SELECT posts.ID
                    FROM {$wpdb->posts} AS posts
                    JOIN {$wpdb->postmeta} AS sub_installments ON posts.ID = sub_installments.post_id
                    AND sub_installments.meta_key IN (" . implode(',', array_fill(0, count($installment_keys), '%s')) . ")
                    AND sub_installments.meta_value != '-1'
                    AND posts.post_status IN (" . implode(',', array_fill(0, count($active_statuses), '%s')) . ')
                )',
                array_merge(
                    $subscription_keys,
                    $order_types,
                    $amount_keys,
                    $paid_statuses,
                    [ wp_date('Y-m-01') ],
                    $installment_keys,
                    $active_statuses
                )
            )
        );

        return $total_amount ? (float) $total_amount : 0;
    }
}
