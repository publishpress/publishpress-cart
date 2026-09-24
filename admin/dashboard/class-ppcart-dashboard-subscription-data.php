<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Monthly subscription sign-up, trial, cancellation, and MRR metrics.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Dashboard_Subscription_Data
{
    public function get_summary()
    {
        $subscriptions_query = $this->get_subscriptions_query('active', '-1', '=');

        return [
            'active_subscriptions' => $subscriptions_query->found_posts,
            'new_sign_ups'         => $this->get_subscription_count('active'),
            'trials'               => $this->get_subscription_count('trialing'),
            'cancellations'        => $this->get_subscription_count('canceled'),
            'renewals'             => $this->get_renewal_count(),
            'mrr'                  => $this->get_mrr($subscriptions_query),
        ];
    }

    private function get_subscriptions_query($status, $installments, $compare)
    {
        return new WP_Query([
            'post_type'      => ppcart_query_post_types('subscription'),
            'post_status'    => $status,
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Dashboard metrics require filtering by installment type.
            'meta_query'     => [
                [
                    'key' => ppcart_meta_key('sub_installments'),
                    'value'   => $installments,
                    'compare' => $compare,
                ],
            ],
        ]);
    }

    private function get_subscription_count($status = 'active')
    {
        $query = new WP_Query([
            'post_type'      => ppcart_query_post_types('subscription'),
            'post_status'    => $status,
            'date_query'     => [
                'after' => wp_date('Y-m-01'),
            ],
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Dashboard count requires filtering subscriptions.
            'meta_query'     => [
                [
                    'key' => ppcart_meta_key('sub_installments'),
                    'value'   => '-1',
                    'compare' => '=',
                ],
            ],
        ]);

        return $query->found_posts;
    }

    private function get_renewal_count()
    {
        $query = new WP_Query([
            'post_type'      => ppcart_query_post_types('order'),
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Renewal metric requires selecting orders with renewal meta.
            'meta_query'     => [
                [
                    'key' => ppcart_meta_key('renewal_order'),
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        return $query->found_posts;
    }

    private function get_mrr($subscriptions_query)
    {
        $total_mrr = 0;

        if ($subscriptions_query->have_posts()) {
            while ($subscriptions_query->have_posts()) {
                $subscriptions_query->the_post();
                $total_mrr += $this->get_monthly_revenue(get_the_ID());
            }
        }

        wp_reset_postdata();

        return ppcart_format_price($total_mrr);
    }

    private function get_monthly_revenue($subscription_id)
    {
        $amount    = (float) ppcart_get_post_meta($subscription_id, 'sub_amount', true);
        $interval  = ppcart_get_post_meta($subscription_id, 'sub_interval', true);
        $frequency = (float) ppcart_get_post_meta($subscription_id, 'frequency', true);
        $frequency = $frequency ? $frequency : 1;
        $multipliers = [
            'day'   => 30 / $frequency,
            'week'  => 4.34524 / $frequency,
            'month' => $frequency,
            'year'  => $frequency / 12,
        ];

        return isset($multipliers[$interval]) ? $amount * $multipliers[$interval] : 0;
    }
}
