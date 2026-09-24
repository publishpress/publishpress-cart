<?php

if (! defined('ABSPATH')) {
    exit;
}


register_post_status('ppcart_pending', [
    'label'                     => __('Pending', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => array_merge(ppcart_query_post_types('subscription'), ppcart_query_post_types('order')),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_paid', [
    'label'                     => __('Paid', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('order'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_refunded', [
    'label'                     => __('Refunded', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('order'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_failed', [
    'label'                     => __('Failed', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('order'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_past_due', [
    'label'                     => __('Past Due', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => array_merge(ppcart_query_post_types('order'), ppcart_query_post_types('subscription')),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Past Due <span class="count">(%s)</span>', 'Past Due <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_uncollectible', [
    'label'                     => __('Uncollectible', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('order'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Uncollectible <span class="count">(%s)</span>', 'Uncollectible <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_active', [
    'label'                     => __('Active', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('subscription'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_unpaid', [
    'label'                     => __('Unpaid', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => array_merge(ppcart_query_post_types('subscription'), ppcart_query_post_types('order')),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Unpaid <span class="count">(%s)</span>', 'Unpaid <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_paused', [
    'label'                     => __('Paused', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('subscription'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Paused <span class="count">(%s)</span>', 'Paused <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_canceled', [
    'label'                     => __('Canceled', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('subscription'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Canceled <span class="count">(%s)</span>', 'Canceled <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_completed', [
    'label'                     => __('Completed', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => array_merge(ppcart_query_post_types('subscription'), ppcart_query_post_types('order')),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_incomplete', [
    'label'                     => __('Incomplete', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('subscription'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Incomplete <span class="count">(%s)</span>', 'Incomplete <span class="count">(%s)</span>', 'publishpress-cart'),
]);

register_post_status('ppcart_trialing', [
    'label'                     => __('Trialing', 'publishpress-cart'),
    'public'                    => true,
    'exclude_from_search'       => false,
    'show_in_admin_all_list'    => true,
    'show_in_admin_status_list' => true,
    'post_type'                 => ppcart_query_post_types('subscription'),
    /* translators: %s: number of posts. */
    'label_count'               => _n_noop('Trialing <span class="count">(%s)</span>', 'Trialing <span class="count">(%s)</span>', 'publishpress-cart'),
]);
