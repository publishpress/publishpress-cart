<?php

/**
 * Aggregate queries used by the admin report screens.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Count and sum the paid orders that belong to each subscription.
 *
 * @param array<int, int|string> $subscription_ids Subscription post IDs.
 * @return array<int, array{count: int, total: float}> Keyed by subscription ID.
 */
function ppcart_get_report_subscription_order_totals($subscription_ids)
{
    global $wpdb;

    $subscription_ids = array_values(array_filter(array_map('absint', (array) $subscription_ids)));
    if (! $subscription_ids) {
        return [];
    }

    $subscription_keys = ppcart_query_meta_keys('subscription_id');
    $amount_keys       = ppcart_query_meta_keys('amount');
    $order_types       = ppcart_query_post_types('order');
    $paid_statuses     = ppcart_query_post_statuses('paid');

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Report aggregate across orders and postmeta.
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT subscription_meta.meta_value AS subscription_id, COUNT(DISTINCT posts.ID) AS order_count, COALESCE(SUM(CAST(amount_meta.meta_value AS DECIMAL(20,6))), 0) AS total_amount
            FROM {$wpdb->posts} posts
            INNER JOIN {$wpdb->postmeta} subscription_meta
                ON posts.ID = subscription_meta.post_id
                AND subscription_meta.meta_key IN (" . implode(',', array_fill(0, count($subscription_keys), '%s')) . ")
            LEFT JOIN {$wpdb->postmeta} amount_meta
                ON posts.ID = amount_meta.post_id
                AND amount_meta.meta_key IN (" . implode(',', array_fill(0, count($amount_keys), '%s')) . ")
            WHERE posts.post_type IN (" . implode(',', array_fill(0, count($order_types), '%s')) . ")
                AND posts.post_status IN (" . implode(',', array_fill(0, count($paid_statuses), '%s')) . ")
                AND subscription_meta.meta_value IN (" . implode(',', array_fill(0, count($subscription_ids), '%d')) . ')
            GROUP BY subscription_meta.meta_value',
            array_merge($subscription_keys, $amount_keys, $order_types, $paid_statuses, $subscription_ids)
        )
    );

    $totals = [];
    foreach ((array) $rows as $row) {
        $totals[ absint($row->subscription_id) ] = [
            'count' => absint($row->order_count),
            'total' => (float) $row->total_amount,
        ];
    }

    return $totals;
}
