<?php

if (! defined('ABSPATH')) {
    exit;
}

function ppcart_schedule_report_period($schedule)
{
    $today    = gmdate('Y-m-d');
    $schedule = function_exists('ppcart_canonical_report_schedule')
        ? ppcart_canonical_report_schedule($schedule)
        : $schedule;

    switch ($schedule) {
        case 'ppcart_weekly':
            $previous = gmdate('Y-m-d', strtotime('-7 days'));
            $next     = gmdate('Y-m-d', strtotime('+7 days'));
            break;
        case 'ppcart_semi_monthly':
            $previous = gmdate('Y-m-d', strtotime('-15 days'));
            $next     = gmdate('Y-m-d', strtotime('+15 days'));
            break;
        case 'ppcart_daily':
        default:
            $previous = gmdate('Y-m-d', strtotime('-1 days'));
            $next     = gmdate('Y-m-d', strtotime('+1 days'));
            break;
    }

    return [
        'date1'    => $previous,
        'date2'    => $today,
        'datenxt'  => $next,
        'date_1'   => date_format(date_create($previous), 'l, M d'),
        'date_2'   => date_format(date_create($today), 'l, M d'),
        'datenxt_1' => date_format(date_create($today), 'l, M d'),
        'datenxt_2' => date_format(date_create($next), 'l, M d'),
    ];
}

function ppcart_schedule_report_date_query($date1, $date2)
{
    $from = new DateTime($date1);
    $to   = new DateTime($date2);

    return [
        [
            'after'     => $from->format('Y-m-d'),
            'before'    => [
                'year'  => $to->format('Y'),
                'month' => $to->format('m'),
                'day'   => $to->format('d'),
            ],
            'inclusive' => true,
        ],
    ];
}

function ppcart_schedule_report_order_args($date1, $date2)
{
    $args = [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => 'any',
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
    ];

    if ('' !== $date1 && '' !== $date2) {
        $args['date_query'] = ppcart_schedule_report_date_query($date1, $date2);
    }

    return $args;
}

function ppcart_schedule_report_refund_args($date1, $date2)
{
    $args = [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => 'refunded',
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
    ];

    if ('' !== $date1 && '' !== $date2) {
        $args['date_query'] = ppcart_schedule_report_date_query($date1, $date2);
    }

    return $args;
}

function ppcart_schedule_report_subscription_args()
{
    return [
        'post_type'      => ppcart_query_post_types('subscription'),
        'post_status'    => [ 'All' ],
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
    ];
}

function ppcart_schedule_report_subscription_summary($start_date, $end_date)
{
    $summary = [
        'canceled_subscription' => 0,
        'renewals'              => [],
        'trials'                => [],
    ];

    $start = DateTime::createFromFormat('Y-m-d', $start_date);
    $end   = DateTime::createFromFormat('Y-m-d', $end_date);
    $query = new WP_Query(ppcart_schedule_report_subscription_args());

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $subscription = new PPCart_Subscription(get_the_ID());

            ppcart_schedule_report_collect_renewal($summary, $subscription, $start, $end);
            ppcart_schedule_report_collect_cancellation($summary, $subscription, $start, $end);
        }
    }

    wp_reset_postdata();

    return $summary;
}

function ppcart_schedule_report_collect_renewal(&$summary, $subscription, $start, $end)
{
    if (empty($subscription->sub_next_bill_date)) {
        return;
    }

    $next_bill_date = ppcart_maybe_format_date($subscription->sub_next_bill_date, 'Y-m-d');
    $next_bill      = DateTime::createFromFormat('Y-m-d', $next_bill_date);

    if (! $next_bill || $next_bill < $start || $next_bill > $end) {
        return;
    }

    $edit_link = get_admin_url(null, "post.php?post={$subscription->id}&action=edit");
    $row       = [
        'id'                 => $subscription->id,
        'customer_name'      => '<a href="' . $edit_link . '">' . $subscription->customer_name . '</a>',
        'product_name'       => $subscription->product_name,
        'sub_next_bill_date' => $subscription->sub_next_bill_date,
        'sub_amount'         => $subscription->sub_amount,
    ];

    if ('pending-payment' === $subscription->sub_status) {
        return;
    }

    if ('trialing' === $subscription->sub_status) {
        $summary['trials'][] = $row;
        return;
    }

    $summary['renewals'][] = $row;
}

function ppcart_schedule_report_collect_cancellation(&$summary, $subscription, $start, $end)
{
    if (empty($subscription->cancel_date)) {
        return;
    }

    $cancel_date = ppcart_maybe_format_date($subscription->cancel_date, 'Y-m-d');
    $cancelled   = DateTime::createFromFormat('Y-m-d', $cancel_date);

    if ($cancelled && $cancelled >= $start && $cancelled <= $end) {
        $summary['canceled_subscription']++;
    }
}

function ppcart_schedule_report_refund_summary($args)
{
    $amounts = [];
    $counts  = [];
    $query   = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $refund_log = ppcart_get_post_meta(get_the_ID(), 'refund_log', true);

            if (! is_array($refund_log)) {
                continue;
            }

            $refund_amount = array_sum(array_map('floatval', array_column($refund_log, 'amount')));
            $amounts[]     = $refund_amount ? $refund_amount : ppcart_get_post_meta(get_the_ID(), 'amount', true);
            $counts[]      = count($refund_log);
        }
    }

    wp_reset_postdata();

    return [
        'amount' => array_sum($amounts),
        'count'  => array_sum($counts),
    ];
}

function ppcart_schedule_report_order_summary($order_args, $subscription_args)
{
    $summary = [
        'carttotal'        => [ 'total' => 0 ],
        'completed_orders' => 0,
        'pending_payment'  => 0,
        'trialing_payment' => 0,
        'failed_payment'   => 0,
        'all_subscription' => count(get_posts(array_merge($order_args, $subscription_args))),
    ];

    $subscription_query = new WP_Query(array_merge($order_args, $subscription_args));
    if ($subscription_query->have_posts()) {
        while ($subscription_query->have_posts()) {
            $subscription_query->the_post();
            $order = new PPCart_Subscription(get_the_ID());

            if ('pending-payment' === $order->status) {
                $summary['all_subscription']--;
            }

            if ('trialing' === $order->status) {
                $summary['trialing_payment']++;
            }
        }
    }
    wp_reset_postdata();

    $order_query = new WP_Query($order_args);
    if ($order_query->have_posts()) {
        while ($order_query->have_posts()) {
            $order_query->the_post();
            $order = new PPCart_Order(get_the_ID());

            if ('completed' === $order->status || 'paid' === $order->status) {
                $summary['completed_orders']++;
            }
            if ('pending-payment' === $order->status) {
                $summary['pending_payment']++;
            }
            if ('failed' === $order->status) {
                $summary['failed_payment']++;
            }

            $summary['carttotal']['total'] += $order->amount;
        }
    }
    wp_reset_postdata();

    return $summary;
}

function ppcart_schedule_report_data()
{
    $schedule = function_exists('ppcart_get_report_schedule')
        ? ppcart_get_report_schedule()
        : get_option('ppcart_report_schedule');
    $period   = ppcart_schedule_report_period($schedule);

    $subscription_summary = ppcart_schedule_report_subscription_summary($period['date2'], $period['datenxt']);
    $order_args           = ppcart_schedule_report_order_args($period['date1'], $period['date2']);
    $refund_summary       = ppcart_schedule_report_refund_summary(ppcart_schedule_report_refund_args($period['date1'], $period['date2']));
    $order_summary        = ppcart_schedule_report_order_summary($order_args, ppcart_schedule_report_subscription_args());

    return array_merge($period, $subscription_summary, $order_summary, [
        'site_name'        => get_bloginfo('name'),
        'schedule'         => $schedule,
        'company_logo'     => get_option('_ppcart_company_logo'),
        'refunded_amount'  => $refund_summary['amount'],
        'refunded_time'    => $refund_summary['count'],
    ]);
}
