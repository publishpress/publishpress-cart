<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filtering via admin query parameters.
if (! isset($_REQUEST['customerid'])) {
    return;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filtering via admin query parameters.
$report_type = isset($_REQUEST['reportstypes']) ? sanitize_key(wp_unslash($_REQUEST['reportstypes'])) : 'order';
if (! in_array($report_type, ['order', 'subscription'], true)) {
    $report_type = 'order';
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filtering via admin query parameters.
$customer = sanitize_email(wp_unslash($_REQUEST['customerid']));
$today    = wp_date('Y-m-d');
$default_from = wp_date('Y-m-d', strtotime('-1 month', strtotime($today)));

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filtering via admin query parameters.
$date = isset($_REQUEST['date']) ? sanitize_text_field(wp_unslash($_REQUEST['date'])) : $default_from . ' to ' . $today;
$date_parts = array_map('trim', explode(' to ', $date));
$from_value = ! empty($date_parts[0]) ? $date_parts[0] : $default_from;
$to_value   = ! empty($date_parts[1]) ? $date_parts[1] : $from_value;
$from_ts    = strtotime($from_value);
$to_ts      = strtotime($to_value);

if (false === $from_ts || false === $to_ts) {
    $from_value = $default_from;
    $to_value   = $today;
    $from_ts    = strtotime($from_value);
    $to_ts      = strtotime($to_value);
    $date       = $from_value . ' to ' . $to_value;
}

$fromdate = $from_value . ' 00:00:00';
$todate   = $to_value . ' 23:59:59';
$date_label = sprintf(
    /* translators: 1: start date, 2: end date. */
    _x('%1$s - %2$s', 'date range', 'publishpress-cart'),
    date_i18n('M j, Y', $from_ts),
    date_i18n('M j, Y', $to_ts)
);

$orders_url = add_query_arg(
    [
        'page'         => PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS,
        'reportstypes' => 'order',
        'customerid'   => $customer,
        'date'         => $date,
    ],
    admin_url('admin.php')
);
$subscriptions_url = add_query_arg(
    [
        'page'         => PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS,
        'reportstypes' => 'subscription',
        'customerid'   => $customer,
        'date'         => $date,
    ],
    admin_url('admin.php')
);
$contacts_url = add_query_arg(['page' => PPCart_Admin_Screens::PAGE_CONTACTS], admin_url('admin.php'));
$orders_list_url = add_query_arg(
    [
        'post_type'   => ppcart_query_post_types('order'),
        'order_email' => $customer,
    ],
    admin_url('edit.php')
);
$subscriptions_list_url = add_query_arg(
    [
        'post_type' => ppcart_query_post_types('subscription'),
        's'         => $customer,
    ],
    admin_url('edit.php')
);
$export_type = 'subscription' === $report_type ? 'subscription' : 'order';
$export_label = 'subscription' === $report_type ? __('Export Subscriptions', 'publishpress-cart') : __('Export Orders', 'publishpress-cart');
$export_url = wp_nonce_url(
    add_query_arg(
        [
            'ppcart-csv-export' => 'customer',
            'type'          => $export_type,
            'emailid'       => $customer,
            'daterange'     => $date,
        ],
        home_url('/')
    ),
    'ppcart_csv_export'
);

$customer_user = get_user_by('email', $customer);
$customer_name = '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Optional display-only customer name from existing report link.
if (isset($_REQUEST['customername'])) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Optional display-only customer name from existing report link.
    $customer_name = sanitize_text_field(wp_unslash($_REQUEST['customername']));
} elseif ($customer_user) {
    $customer_name = trim((string) $customer_user->first_name . ' ' . (string) $customer_user->last_name);
}

$date_query = [
    [
        'column'    => 'post_date',
        'after'     => $fromdate,
        'before'    => $todate,
        'inclusive' => true,
    ],
];
$customer_meta_query = [
    [
        'key' => ppcart_meta_key('email'),
        'value' => $customer,
    ],
];

$order_rows = [];
$gross_paid_amount = 0;
$refunded_amount = 0;
$lifetime_value = 0;
$paid_order_count = 0;
$order_count = 0;
$product_ids = [];
$latest_order_data = null;
$latest_order_id = 0;
$first_order_date = '';
$last_order_date = '';

$lifetime_order_results = new WP_Query(
    [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => 'any',
        'orderby'        => 'date',
        'order'          => 'ASC',
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Customer LTV requires filtering orders by customer email meta.
        'meta_query'     => $customer_meta_query,
    ]
);

if ($lifetime_order_results->have_posts()) {
    while ($lifetime_order_results->have_posts()) {
        $lifetime_order_results->the_post();
        $lifetime_order_id = get_the_ID();
        $order_status = PPCart_Status_Labels::edit_select_value(PPCart_Status_Labels::logical_from_post($lifetime_order_id));

        if ('' === $first_order_date) {
            $first_order_date = get_the_time('M j, Y', $lifetime_order_id);
        }
        $last_order_date = get_the_time('M j, Y', $lifetime_order_id);
        $latest_order_id = $lifetime_order_id;

        if ('pending' === $order_status) {
            continue;
        }

        $product_id = absint(ppcart_get_post_meta($lifetime_order_id, 'product_id', true));
        if ($product_id) {
            $product_ids[$product_id] = true;
        }

        if ('paid' !== $order_status) {
            continue;
        }

        $total_amount = (float) ppcart_get_post_meta($lifetime_order_id, 'amount', true);

        if ('refunded' === ppcart_get_post_meta($lifetime_order_id, 'payment_status', true)) {
            $refund_logs = ppcart_get_post_meta($lifetime_order_id, 'refund_log', true);
            if (is_array($refund_logs)) {
                $refund_amount_values = array_map(
                    'floatval',
                    array_column($refund_logs, 'amount')
                );
                $total_amount -= array_sum($refund_amount_values);
            }
        }

        $lifetime_value += max(0, $total_amount);
    }
}
wp_reset_postdata();

if ($latest_order_id) {
    $latest_order = new PPCart_Order($latest_order_id);
    $latest_order_data = (object) $latest_order->get_data();
    if ('' === $customer_name) {
        $customer_name = trim((string) $latest_order_data->firstname . ' ' . (string) $latest_order_data->lastname);
    }
}

$order_results = new WP_Query(
    [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => 'any',
        'orderby'        => 'date',
        'order'          => 'ASC',
        'date_query'     => $date_query,
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Customer report requires filtering orders by customer email meta.
        'meta_query'     => $customer_meta_query,
    ]
);

if ($order_results->have_posts()) {
    while ($order_results->have_posts()) {
        $order_results->the_post();
        $order_post_id = get_the_ID();
        $cart_order = new PPCart_Order($order_post_id);
        $order_data = (object) $cart_order->get_data();
        $order_status = PPCart_Status_Labels::edit_select_value(PPCart_Status_Labels::logical_from_post($order_post_id));

        if ('pending' === $order_status) {
            continue;
        }

        $order_count++;
        $product_id = isset($order_data->product_id) ? absint($order_data->product_id) : 0;

        $gross_amount = (float) $order_data->amount;
        $display_amount = $gross_amount;
        $order_refunded_amount = 0;
        $is_refunded = 'refunded' === $order_data->payment_status;
        if ($is_refunded && isset($order_data->refund_log) && is_array($order_data->refund_log)) {
            $refund_amount_values = array_map(
                'floatval',
                array_column($order_data->refund_log, 'amount')
            );
            $order_refunded_amount = array_sum($refund_amount_values);
            $display_amount = max(0, $gross_amount - $order_refunded_amount);
            $refunded_amount += $order_refunded_amount;
        }

        if ('paid' === $order_status) {
            $paid_order_count++;
            $gross_paid_amount += $gross_amount;
        }

        $order_type_label = $order_data->item_name ?? '';
        $order_type_class = '';
        if (isset($order_data->us_parent)) {
            $order_type_label = __('Upsell', 'publishpress-cart');
            $order_type_class = 'upsell';
        } elseif (isset($order_data->ds_parent)) {
            $order_type_label = __('Downsell', 'publishpress-cart');
            $order_type_class = 'upsell-2';
        }

        $amount_html = $is_refunded
            ? '<s>' . ppcart_format_price($order_data->amount) . '</s> ' . ppcart_format_price($display_amount)
            : ppcart_format_price($display_amount);

        $order_rows[] = [
            'id'               => $order_post_id,
            'date'             => get_the_time('M j, Y', $order_post_id),
            'edit_url'         => ppcart_get_edit_post_url($order_post_id),
            'product_title'    => $product_id ? get_the_title($product_id) : __('Order', 'publishpress-cart'),
            'type_label'       => $order_type_label,
            'type_class'       => $order_type_class,
            'status'           => $order_status,
            'status_label'     => $order_data->status_label,
            'amount_html'      => $amount_html,
            'is_refunded'      => $is_refunded,
        ];

        if (isset($order_data->bump_id)) {
            $order_rows[] = [
                'id'            => $order_post_id,
                'date'          => get_the_time('M j, Y', $order_post_id),
                'edit_url'      => ppcart_get_edit_post_url($order_post_id),
                'product_title' => get_the_title($order_data->bump_id),
                'type_label'    => __('Order Bump', 'publishpress-cart'),
                'type_class'    => 'bump',
                'status'        => $order_status,
                'status_label'  => $order_data->status_label,
                'amount_html'   => ppcart_format_price($order_data->bump_amt),
                'is_refunded'   => $is_refunded,
            ];
        }
    }
}
wp_reset_postdata();

$subscription_rows = [];
$subscription_items = [];
$subscription_ids = [];
$subscription_count = 0;
$active_subscription_count = 0;
$subscription_revenue = 0;
$next_payment_dates = [];

$subscription_results = new WP_Query(
    [
        'post_type'      => ppcart_query_post_types('subscription'),
        'post_status'    => 'any',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'date_query'     => $date_query,
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Customer report requires filtering subscriptions by customer email meta.
        'meta_query'     => $customer_meta_query,
    ]
);

if ($subscription_results->have_posts()) {
    while ($subscription_results->have_posts()) {
        $subscription_results->the_post();
        $subscription_post_id = get_the_ID();
        $subscription_status = PPCart_Status_Labels::edit_select_value(PPCart_Status_Labels::logical_from_post($subscription_post_id));

        if ('pending' === $subscription_status) {
            continue;
        }

        $subscription_ids[] = $subscription_post_id;
        $subscription_items[] = [
            'id'           => $subscription_post_id,
            'status'       => $subscription_status,
            'installments' => ppcart_get_post_meta($subscription_post_id, 'sub_installments', true),
        ];
    }
}
wp_reset_postdata();

$subscription_order_totals = [];
if (! empty($subscription_ids)) {
    $subscription_id_placeholders = implode(',', array_fill(0, count($subscription_ids), '%d'));
    $subscription_order_sql = "SELECT subscription_meta.meta_value AS subscription_id, COUNT(DISTINCT posts.ID) AS order_count, COALESCE(SUM(CAST(amount_meta.meta_value AS DECIMAL(20,6))), 0) AS total_amount
                        FROM {$wpdb->posts} posts
                        INNER JOIN {$wpdb->postmeta} subscription_meta
                            ON posts.ID = subscription_meta.post_id
                            AND subscription_meta.meta_key IN (" . ppcart_sql_in_meta_keys('subscription_id') . ")
                        LEFT JOIN {$wpdb->postmeta} amount_meta
                            ON posts.ID = amount_meta.post_id
                            AND amount_meta.meta_key IN (" . ppcart_sql_in_meta_keys('amount') . ")
                        WHERE posts.post_type IN (" . ppcart_sql_in_post_types('order') . ")
                            AND posts.post_status IN (" . ppcart_sql_in_post_statuses('paid') . ")
                            AND subscription_meta.meta_value IN ($subscription_id_placeholders)
                        GROUP BY subscription_meta.meta_value";
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Report aggregate query uses generated integer placeholders for the subscription ID list.
    $subscription_order_rows = $wpdb->get_results(
        $wpdb->prepare(
            $subscription_order_sql,
            ...$subscription_ids
        )
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

    foreach ($subscription_order_rows as $subscription_order_row) {
        $subscription_order_totals[absint($subscription_order_row->subscription_id)] = [
            'count' => absint($subscription_order_row->order_count),
            'total' => (float) $subscription_order_row->total_amount,
        ];
    }
}

foreach ($subscription_items as $subscription_item) {
    $subscription_post_id = $subscription_item['id'];
    $subscription_status = $subscription_item['status'];
    $installments = $subscription_item['installments'];
    $subscription_order_total = $subscription_order_totals[$subscription_post_id]
        ?? [
            'count' => 0,
            'total' => 0,
        ];
    $total_amount = $subscription_order_total['total'];
    $payments_remaining = '&infin;';

    if ($installments > 0) {
        $payments_remaining = max(0, (int) $installments - (int) $subscription_order_total['count']);
    }

    $subscription = new PPCart_Subscription($subscription_post_id);
    $data = (object) $subscription->get_data();
    $subscription_count++;
    $subscription_revenue += $total_amount;
    if (in_array($subscription_status, ['active', 'trialing'], true)) {
        $active_subscription_count++;
    }
    if (! empty($data->next_pay_date) && 'n/a' !== $data->next_pay_date) {
        $next_payment_timestamp = strtotime($data->next_pay_date);
        if (false !== $next_payment_timestamp && $next_payment_timestamp >= strtotime(wp_date('Y-m-d'))) {
            $next_payment_dates[] = [
                'timestamp' => $next_payment_timestamp,
                'label'     => $data->next_pay_date,
            ];
        }
    }

    $subscription_rows[] = [
        'id'                 => $subscription_post_id,
        'edit_url'           => add_query_arg(['post' => $subscription_post_id, 'action' => 'edit'], admin_url('post.php')),
        'date'               => get_the_time('M j, Y', $subscription_post_id),
        'product_title'      => get_the_title(ppcart_get_post_meta($subscription_post_id, 'product_id', true)),
        'plan'               => $subscription->sub_item_name,
        'pay_interval'       => $data->sub_payment,
        'status'             => $subscription_status,
        'status_label'       => $subscription->get_status(),
        'total_revenue_html' => ppcart_format_price($total_amount),
        'payments_remaining' => $payments_remaining,
    ];
}

if ('' === trim($customer_name)) {
    $customer_name = __('Guest customer', 'publishpress-cart');
}

$net_amount = max(0, $gross_paid_amount - $refunded_amount);
$average_order_amount = $paid_order_count > 0 ? $net_amount / $paid_order_count : 0;
usort(
    $next_payment_dates,
    function ($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    }
);
$next_payment_date = ! empty($next_payment_dates) ? $next_payment_dates[0]['label'] : '';
$location_parts = [];
if ($latest_order_data) {
    foreach (['city', 'state', 'country'] as $location_key) {
        if (! empty($latest_order_data->$location_key)) {
            $location_parts[] = $latest_order_data->$location_key;
        }
    }
}
$customer_phone = $latest_order_data && ! empty($latest_order_data->phone) ? $latest_order_data->phone : '';
if ('' === $customer_phone && $customer_user) {
    $customer_phone = ppcart_get_user_meta($customer_user->ID, 'phone', true);
}
$customer_gateway = $latest_order_data && ! empty($latest_order_data->pay_method) ? $latest_order_data->pay_method : '';
$customer_account = $latest_order_data && ! empty($latest_order_data->customer_id) ? $latest_order_data->customer_id : '';

include __DIR__ . '/customer-report/page.php';
