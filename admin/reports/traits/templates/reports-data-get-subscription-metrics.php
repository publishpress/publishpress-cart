<?php

if (! defined('ABSPATH')) {
    exit;
}


$subscription_args = [
    'post_type'      => ppcart_query_post_types('subscription'),
    'post_status'    => 'any',
    // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
];

if (! empty($meta_query) && is_array($meta_query)) {
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Report identity filters must constrain subscriptions.
    $subscription_args['meta_query'] = $meta_query;
}

$query = new WP_Query($subscription_args);

$metrics = [
    'active'          => 0,
    'active_at_start' => 0,
    'new'             => 0,
    'canceled'        => 0,
    'mrr'             => 0,
    'churn_rate'      => 0,
    'recurring'       => 0,
];
$period_start = empty($period['is_all_time']) ? $period['from']->getTimestamp() : null;

foreach ($query->posts as $subscription_id) {
    $subscription_status = PPCart_Status_Labels::logical_from_post($subscription_id);
    $created_timestamp = $this->get_report_timestamp(get_the_date('Y-m-d H:i:s', $subscription_id));
    $cancel_timestamp  = false;

    if (in_array($subscription_status, [ 'active', 'trialing', 'past_due' ], true)) {
        $metrics['active']++;
    }

    if ($this->is_date_in_period(get_the_date('Y-m-d', $subscription_id), $period)) {
        $metrics['new']++;
    }

    if ('canceled' === $subscription_status) {
        $cancel_date = ppcart_get_post_meta($subscription_id, 'cancel_date', true);

        if (! $cancel_date) {
            $cancel_date = ppcart_get_post_meta($subscription_id, 'subscription_canceled_date', true);
        }

        if (! $cancel_date) {
            $cancel_date = get_the_modified_date('Y-m-d', $subscription_id);
        }

        $cancel_timestamp = $this->get_report_timestamp($cancel_date);

        if ($this->is_date_in_period($cancel_date, $period)) {
            $metrics['canceled']++;
        }
    }

    if (null !== $period_start && false !== $created_timestamp && $created_timestamp <= $period_start) {
        if (in_array($subscription_status, [ 'active', 'trialing', 'past_due' ], true)) {
            $metrics['active_at_start']++;
        } elseif ('canceled' === $subscription_status && (false === $cancel_timestamp || $cancel_timestamp >= $period_start)) {
            $metrics['active_at_start']++;
        }
    }

    $installments = ppcart_get_post_meta($subscription_id, 'sub_installments', true);

    if (in_array($subscription_status, [ 'active', 'trialing', 'past_due' ], true) && '-1' === (string) $installments) {
        $metrics['recurring']++;
        $metrics['mrr'] += $this->normalize_subscription_mrr($subscription_id);
    }
}

wp_reset_postdata();

$churn_base = ! empty($period['is_all_time']) ? $metrics['active'] + $metrics['canceled'] : $metrics['active_at_start'];

if ($churn_base > 0) {
    $metrics['churn_rate'] = ($metrics['canceled'] / $churn_base) * 100;
}

return $metrics;
