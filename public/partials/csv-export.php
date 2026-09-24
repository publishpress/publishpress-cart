<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb, $ppcart_currency_symbol, $ppcart_public;

remove_filter('the_title', [ $ppcart_public, 'public_product_name' ]);

function ppcart_csv_escape_cell($value)
{
    $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, 'UTF-8');

    if ('' !== $value && preg_match('/^[=\+\-@\t\r\n]/', $value)) {
        $value = "'" . $value;
    }

    if (false !== strpbrk($value, "\",\r\n")) {
        $value = '"' . str_replace('"', '""', $value) . '"';
    }

    return $value;
}


function ppcart_csv_row($values)
{
    return implode(',', array_map('ppcart_csv_escape_cell', $values)) . "\n";
}


$csv_output_report = '';
$filename = strtolower(apply_filters('ppcart_plugin_title', 'PublishPress Cart'));
$export_mode = sanitize_key((string) ppcart_filter_input_request('ppcart-csv-export', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$export_type = sanitize_key((string) ppcart_filter_input_request('type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$export_date_range = sanitize_text_field((string) ppcart_filter_input_request('daterange', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$export_email = sanitize_email((string) ppcart_filter_input_request('emailid', FILTER_SANITIZE_EMAIL));
if ($export_mode === 'contacts') {
    $csv_output_report .= ppcart_csv_row([
        esc_html__('First Name', 'publishpress-cart'),
        esc_html__('Last Name', 'publishpress-cart'),
        esc_html__('Email', 'publishpress-cart'),
        esc_html__('Orders', 'publishpress-cart'),
        esc_html__('Last Order Date', 'publishpress-cart'),
    ]);
    $order_types = ppcart_query_post_types('order');
    if (! $order_types) {
        $order_types = [''];
    }
    $order_in = implode(',', array_fill(0, count($order_types), '%s'));

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- One-time export; $order_in is %s placeholders bound in prepare().
    $get_user = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT {$wpdb->posts}.ID, {$wpdb->postmeta}.meta_value
            FROM {$wpdb->posts}
            INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
            WHERE {$wpdb->postmeta}.meta_key = %s
                AND {$wpdb->posts}.post_type IN ($order_in)
                AND {$wpdb->posts}.post_status NOT IN (%s, %s)
            ORDER BY {$wpdb->posts}.post_date DESC",
            array_merge(
                [ppcart_meta_key('email')],
                $order_types,
                ['trash', 'auto-draft']
            )
        )
    );

    $get_users = array_count_values(array_map('strtolower', array_column($get_user, 'meta_value')));

    foreach ($get_users as $key => $value) {
        $get_date = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_date
                FROM {$wpdb->posts}
                INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
                WHERE {$wpdb->postmeta}.meta_key = %s
                    AND {$wpdb->postmeta}.meta_value = %s
                    AND {$wpdb->posts}.post_type IN ($order_in)
                    AND {$wpdb->posts}.post_status NOT IN (%s, %s)
                ORDER BY {$wpdb->posts}.post_date DESC
                LIMIT 1",
                array_merge(
                    [ppcart_meta_key('email'), $key],
                    $order_types,
                    ['trash', 'auto-draft']
                )
            )
        );

        $csv_output_report .= ppcart_csv_row([
            ppcart_get_post_meta($get_date->ID, 'firstname', true),
            ppcart_get_post_meta($get_date->ID, 'lastname', true),
            ppcart_get_post_meta($get_date->ID, 'email', true),
            $value,
            get_the_time('M j Y', $get_date->ID),
        ]);
    }
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
} else {
    $daterange = $export_date_range;
    $dates = explode(" to ", $daterange);
    $fromdate = $dates[0] . " 00:00:00";
    $todate = $dates[1] ?? $dates[0];
    $todate .= " 23:59:59";
    $customer = false;
    $report_filters = ppcart_parse_report_filters(
        [
            'date'       => $export_date_range,
            'customer'   => (string) ppcart_filter_input_request('customer', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'emailid'    => $export_email,
            'product_id' => (string) ppcart_filter_input_request('product_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ]
    );

    if ($export_mode === 'customer') {
        $customer = $export_email;
        $filename = esc_html($customer);
        if ('' !== $report_filters['customer']) {
            $customer = $report_filters['customer'];
        }
    } elseif ('' !== $report_filters['customer']) {
        $customer = $report_filters['customer'];
    }

    if ($export_type === 'order') {
        $args = [
            'post_type' => ppcart_query_post_types('order'),
            'post_status' => 'any',
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
        ];
        if ('' !== trim((string) $daterange)) {
            $args['date_query'] = [
                [
                    'post_date' => 'post_date',
                    'after' => $fromdate,
                ],
                [
                    'post_date' => 'post_date',
                    'before'  => $todate,
                ],
            ];
        }
        $args = ppcart_apply_report_filters_to_query_args(
            $args,
            [
                'customer'   => $customer ? strtolower(sanitize_email((string) $customer)) : '',
                'product_id' => $report_filters['product_id'],
            ]
        );

        $results = new WP_Query($args);
        $columns  = apply_filters('ppcart_export_columns', [
            esc_html__('ID', 'publishpress-cart'),
            esc_html__('Date', 'publishpress-cart'),
            esc_html__('First Name', 'publishpress-cart'),
            esc_html__('Last Name', 'publishpress-cart'),
            esc_html__('Email', 'publishpress-cart'),
            esc_html__('Phone', 'publishpress-cart'),
            esc_html__('Address 1', 'publishpress-cart'),
            esc_html__('Address 2', 'publishpress-cart'),
            esc_html__('City', 'publishpress-cart'),
            esc_html__('State', 'publishpress-cart'),
            esc_html__('Zip', 'publishpress-cart'),
            esc_html__('Country', 'publishpress-cart'),
            esc_html__('Amount Paid', 'publishpress-cart'),
            esc_html__('Product Name', 'publishpress-cart'),
            esc_html__('Payment Plan', 'publishpress-cart'),
            esc_html__('Order Bumps', 'publishpress-cart'),
            esc_html__('Status', 'publishpress-cart'),
            esc_html__('Coupon', 'publishpress-cart'),
            esc_html__('Purchase URL', 'publishpress-cart'),
            esc_html__('IP Address', 'publishpress-cart'),
            esc_html__('Custom Fields', 'publishpress-cart'),
            esc_html__('Opted-in', 'publishpress-cart'),
        ]);
        $csv_output_report .= ppcart_csv_row($columns);
        if ($results->have_posts()) {
            while ($results->have_posts()) {
                $results->the_post();
                $current_order_post_id = get_the_ID();
                $order_status = PPCart_Status_Labels::edit_select_value(PPCart_Status_Labels::logical_from_post($current_order_post_id));
                if ($order_status != 'pending') {
                    if (ppcart_get_post_meta($post->ID, 'payment_status', true) == 'refunded') {
                        $refund_logs_entrie = ppcart_get_post_meta($post->ID, 'refund_log', true);
                        $total_amount = ppcart_get_post_meta($post->ID, 'amount', true);
                        if (is_array($refund_logs_entrie)) {
                            $refund_amount_values = array_map(
                                'floatval',
                                array_column($refund_logs_entrie, 'amount')
                            );
                            $refund_amount = array_sum($refund_amount_values);
                            $total_amount = floatval(ppcart_get_post_meta($post->ID, 'amount', true)) - $refund_amount;
                            $refundedarray[] = $refund_amount;
                        }
                    } else {
                        $total_amount = ppcart_get_post_meta($post->ID, 'amount', true);
                    }
                    $ppcart_order = new PPCart_Order($current_order_post_id);

                    $custom_fields = [];
                    if ($ppcart_order->custom_fields) {
                        foreach ($ppcart_order->custom_fields as $k => $v) {
                            if (is_array($v['value'])) {
                                $value = [];
                                for ($i = 0; $i < count($v['value']); $i++) {
                                    $value[] = (isset($v['value_label'][$i])) ? $v['value_label'][$i] : $v['value'][$i];
                                }
                                $value = implode(', ', $value);
                            } else {
                                $value = (isset($v['value_label'])) ? $v['value_label'] : $v['value'];
                            }

                            $custom_fields[] = $v['label'] . ': ' . $value;
                        }
                    }

                    $row = [
                        $ppcart_order->id,
                        get_the_date('Y-m-d', $ppcart_order->id),
                        $ppcart_order->firstname,
                        $ppcart_order->lastname,
                        $ppcart_order->email,
                        $ppcart_order->phone,
                        $ppcart_order->address1,
                        $ppcart_order->address2,
                        $ppcart_order->city,
                        $ppcart_order->state,
                        $ppcart_order->zip,
                        $ppcart_order->country,
                        ppcart_format_number($ppcart_order->amount, $string = true),
                        $ppcart_order->product_name,
                        $ppcart_order->option_id,
                        ($ppcart_order->order_bumps) ? implode("\n", wp_list_pluck($ppcart_order->order_bumps, 'name')) : '',
                        $ppcart_order->get_status(),
                        $ppcart_order->coupon_id,
                        $ppcart_order->page_url,
                        $ppcart_order->ip_address,
                        ($custom_fields) ? implode("\n", $custom_fields) : '',
                        $ppcart_order->consent,
                    ];
                    $csv_output_report .= ppcart_csv_row($row);
                }
            }
        }
    } else {
        $csv_output_report .= ppcart_csv_row([
            esc_html__('Date', 'publishpress-cart'),
            esc_html__('Product Name', 'publishpress-cart'),
            esc_html__('Payment Plan', 'publishpress-cart'),
            esc_html__('Pay Interval', 'publishpress-cart'),
            esc_html__('Status', 'publishpress-cart'),
            esc_html__('Total Revenue', 'publishpress-cart'),
            esc_html__('Remaining Payments', 'publishpress-cart'),
        ]);
        $subscription_args = ['post_type' => ppcart_query_post_types('subscription'),
            'post_status' => 'any',
            'date_query' => [
                [
                'post_date' => 'post_date',
                'after' => $fromdate,
                ],
                [
                'post_date' => 'post_date',
                'before'  => $todate,
                ],
            ],
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
        ];
        if ($customer) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Export must filter subscriptions by customer email.
            $subscription_args['meta_query'] = [
                [
                    'key' => ppcart_meta_key('email'),
                    'value' => $customer,
                ],
            ];
        }

        $subscription_results = new WP_Query($subscription_args);
        if ($subscription_results->have_posts()) {
            while ($subscription_results->have_posts()) {
                $subscription_results->the_post();
                $current_subscription_post_id = get_the_ID();
                $amount = ppcart_get_post_meta($post->ID, 'amount', true);
                $subscription_status = PPCart_Status_Labels::edit_select_value(PPCart_Status_Labels::logical_from_post($post->ID));
                if ($subscription_status != 'pending') {
                    $total_amount = 0;
                    $installments = ppcart_get_post_meta($post->ID, 'sub_installments', true) ?? -1;
                    $interval = ppcart_get_post_meta($post->ID, 'sub_interval', true);
                    if ($installments > 1) {
                        $dateTime = DateTime::createFromFormat('Y-m-d', get_the_time('Y-m-d', $post->ID));
                        $dateTime->add(DateInterval::createFromDateString($installments . ' ' . $interval . 's'));
                        $expiresdate = $dateTime->format('Y-m-d');
                    }
                    $date = gmdate('Y-m-d');
                    if (ppcart_get_post_meta($post->ID, 'sub_installments', true) == '-1') {
                        $expiresdate =  get_the_time('Y-m-d', $post->ID);
                        $diff = strtotime($expiresdate) - strtotime($date);
                    } else {
                        $diff = strtotime($date) - strtotime($expiresdate);
                    }
                    $installments = ppcart_get_post_meta($post->ID, 'sub_installments', true);
                    $payments_remaining = '';

                    if ($installments > 0) {
                        $payments_remaining = $installments;

                        // The Query
                        $args = [
                            'post_type' => ppcart_query_post_types('order'),
                            'orderby' => 'date',
                            'order'   => 'ASC',
                            'post_status' => 'paid',
                            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Export must lookup related paid orders by subscription ID.
                            'meta_query' => [
                                [
                                    'key' => ppcart_meta_key('subscription_id'),
                                    'value' => $post->ID,
                                ],
                            ],
                        ];
                        $the_query = new WP_Query($args);

                        // The Loop
                        if ($the_query->have_posts()) {
                            $num = $the_query->post_count;
                            $payments_remaining = $installments - $num;

                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                $total_amount += ppcart_get_post_meta(get_the_ID(), 'amount', true);
                            }
                        }
                        /* Restore original Post Data */
                        wp_reset_postdata();
                    }

                    $ppcart_order = new PPCart_Order($post->ID);

                    $payments_remaining_label = $payments_remaining;
                    if ($subscription_status == 'canceled') {
                        $payments_remaining_label = esc_html__('Canceled', 'publishpress-cart');
                    } elseif ($subscription_status == 'paused') {
                        $payments_remaining_label = esc_html__('Paused', 'publishpress-cart');
                    } elseif (ppcart_get_post_meta($post->ID, 'sub_installments', true) == '-1') {
                        $payments_remaining_label = esc_html__('Never expires', 'publishpress-cart');
                    }

                    $csv_output_report .= ppcart_csv_row([
                        get_the_time('Y-m-d', $post->ID),
                        get_the_title(ppcart_get_post_meta($post->ID, 'product_id', true)),
                        $ppcart_order->sub_item_name,
                        $ppcart_order->sub_payment_terms_plain,
                        $ppcart_order->get_status(),
                        ppcart_format_price($total_amount, $html = false),
                        $payments_remaining_label,
                    ]);
                }
            }
        }
    }
}

header("Pragma: public");
header("Expires: 0");
header('Content-Encoding: UTF-8');
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"" . $filename . "-" . $export_type . "-export.csv\";");
header("Content-Transfer-Encoding: binary");
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV stream intentionally outputs generated raw CSV content.
echo $csv_output_report;
exit();
