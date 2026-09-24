<?php

if (! defined('ABSPATH')) {
    exit;
}


add_action('ppcart_subscription_reminder_event', 'ppcart_notification_send', 10, 2);

/**
 * Schedule daily event for checking next bill dates
 * @param $type|string Either renewal or trial ending
 */

function ppcart_maybe_schedule_reminders($type)
{
    if (get_option('_ppcart_email_' . $type . '_enable') && !wp_next_scheduled('ppcart_daily_events', [$type])) {
        wp_schedule_event(strtotime('+1 hour'), 'daily', 'ppcart_daily_events', [$type]);
    }
}

/**
 * Remove daily event for checking next bill dates
 * @param $type|string Either renewal or trial ending
 */

function ppcart_clear_reminders($type)
{
    if (wp_next_scheduled('ppcart_daily_events', [$type])) {
        wp_clear_scheduled_hook('ppcart_daily_events', [$type]);
    }
}

/**
 * Determine if we should schedule a daily event hook for reminders based on option value
 */

add_action('update_option__ppcart_email_reminder_enable', 'ppcart_daily_event_reminder_activation', 10, 3);
function ppcart_daily_event_reminder_activation($old_value, $value, $option)
{
    if ($value) {
        ppcart_maybe_schedule_reminders('reminder');
    } else {
        ppcart_clear_reminders('reminder');
    }
}

/**
 * Determine if we should schedule a daily event hook for trial ending reminders based on option value
 */

add_action('update_option__ppcart_email_trial_ending_enable', 'ppcart_daily_event_trial_reminder_activation', 10, 3);
function ppcart_daily_event_trial_reminder_activation($old_value, $value, $option)
{
    if ($value) {
        ppcart_maybe_schedule_reminders('trial_ending');
    } else {
        ppcart_clear_reminders('trial_ending');
    }
}

/**
 *  * Find subscriptions with upcoming renewals or trials ending and schedule a reminder
 * @param $type|string Either renewal or trial ending email
 *
 * Eg: reminder days:  4
 * renewal:            4th
 * email scheduled:    30th
 * email sends:        31st
 *
 */

add_action('ppcart_daily_events', 'ppcart_find_upcoming_renewals');
function ppcart_find_upcoming_renewals($type)
{

    if (!get_option('_ppcart_email_' . $type . '_enable')) {
        return;
    }

    $days = get_option('_ppcart_email_' . $type . '_days', 1);

    $date = new DateTime();
    $date->modify('+' . ($days + 1) . ' day'); // lookup and schedule 1 day early in case of renewal times that might miss the cutoff

    $timestamp  = $date->format('U');
    $beginOfDay = strtotime("today", $timestamp);
    $endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;

    $status = $type == 'reminder' ? 'active' : 'trialing';

    $args = [
        'post_type' => ppcart_query_post_types('subscription'),
        'post_status' => $status,
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Subscription reminders require date-window filtering on subscription meta.
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => ppcart_meta_key('sub_next_bill_date'),
                'value' => $beginOfDay,
                'type' => 'numeric',
                'compare' => '>=',
            ],
            [
                'key' => ppcart_meta_key('sub_next_bill_date'),
                'value' => $endOfDay,
                'type' => 'numeric',
                'compare' => '<=',
            ],
        ],
    ];

    $posts = get_posts($args);
    if (!empty($posts)) {
        foreach ($posts as $post) {
            $sub = new PPCart_Subscription($post->ID);
            ppcart_schedule_reminder($sub, $days);
        }
    }

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Secondary reminder pass requires exact next-bill-date meta lookup.
    $args['meta_query'] = [
            [
                'key' => ppcart_meta_key('sub_next_bill_date'),
                'value' => $date->format('Y-m-d'),
            ],
        ];

    $posts = get_posts($args);
    if (!empty($posts)) {
        foreach ($posts as $post) {
            $sub = new PPCart_Subscription($post->ID);
            ppcart_schedule_reminder($sub, $days);
        }
    }
}

/**
 * Schedule a subscription reminder
 * @param $sub|Object PPCart_Subscription object
 * @param $days|String Number of days before next bill date to send reminder
 */

function ppcart_schedule_reminder($sub, $days)
{

    $time = $sub->sub_next_bill_date;
    $type = $sub->status == 'trialing' ? 'trial_ending' : 'reminder';

    if (!is_numeric($time)) {
        $date = new DateTime($time);
        $date = $date->format('U');
    } else {
        $date = new DateTime();
        $date->setTimestamp($time);
    }

    $date->modify("-{$days} day");

    if (! wp_next_scheduled('ppcart_subscription_reminder_event', [$type, $sub->get_data()])) {
        wp_schedule_single_event($date->format('U'), 'ppcart_subscription_reminder_event', [$type, $sub->get_data()]);
    }
}

/**
 * Set subscription status to 'canceled'
 * @param $sub|Object PPCart_Subscription object
 */

add_action('ppcart_cancel_subscription_event', 'ppcart_run_scheduled_cancellation', 10, 1);
function ppcart_run_scheduled_cancellation($sub)
{
    $sub->status = 'canceled';
    $sub->sub_status = 'canceled';
    $sub->store();
}
