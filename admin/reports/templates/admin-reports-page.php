<?php

if (! defined('ABSPATH')) {
    exit;
}


$report_filters = ppcart_parse_report_filters();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only date filter for report rendering.
$date = (null === $report_filters['date']) ? date_i18n('Y-m-d') : $report_filters['date'];
$report_period = $this->get_report_period($date);
$date          = $report_period['raw'];
$report_filters['date'] = $date;
$export_query = [
    'ppcart-csv-export' => 'reports',
    'type'          => 'order',
    'daterange'     => $date,
];
if ('' !== $report_filters['customer']) {
    $export_query['customer'] = $report_filters['customer'];
}
if ($report_filters['product_id'] > 0) {
    $export_query['product_id'] = $report_filters['product_id'];
}
$clear_url = admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_REPORTS);
$customer_search_value = '' !== $report_filters['customer']
    ? ppcart_get_report_customer_label($report_filters['customer'])
    : '';
$product_search_value = $report_filters['product_id'] > 0
    ? ppcart_get_report_product_label($report_filters['product_id'])
    : '';
?>
<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap ppcart-reports-wrap">
    <div class="pp-columns-wrapper pp-enable-sidebar">
        <div class="pp-column-left">

            <?php settings_errors(); ?>

            <h1><?php echo esc_html(apply_filters('ppcart_plugin_title', $this->plugin_title)); ?></h1>

            <div class="ppcart-reports ppcart-reports-page">
                <div class="ppcart-reports-header">
                    <div class="ppcart-reports-title">
                        <h2><?php esc_html_e('Reports', 'publishpress-cart'); ?></h2>
                        <p><?php
                            /* translators: %s: report period label. */
                            echo esc_html(sprintf(__('Performance summary for %s', 'publishpress-cart'), $report_period['label'])); ?></p>
                    </div>

                    <select id="date-select" style="display:none;" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-reports-date-preset')); ?>">
                        <?php
                        $match_found = false;
$options = [
    'Today' => date_i18n("Y-m-d"),
    'Yesterday' => date_i18n("Y-m-d", strtotime("-1 days")),
    'Last 7 days' => date_i18n("Y-m-d", strtotime("-7 days")) . ' to ' . date_i18n("Y-m-d"),
    'Last 30 days' => date_i18n("Y-m-d", strtotime("-30 days")) . ' to ' . date_i18n("Y-m-d"),
    'This Month' => date_i18n("Y-m-d", strtotime("first day of this month")) . ' to ' . date_i18n("Y-m-d"),
    'Last Month' => date_i18n("Y-m-d", strtotime("first day of last month")) . ' to ' . date_i18n("Y-m-d", strtotime("last day of last month")),
    'All Time' => '',
    'Custom' => 'custom',
];
foreach ($options as $k => $v) {
    $checked = '';
    if ($v == $date) {
        $checked = 'selected';
        $match_found = true;
    }

    if ($v == 'custom' && !$match_found) {
        $checked = 'selected';
    }

    echo '<option value="' . esc_attr($v) . '" ' . esc_attr($checked) . '>' . esc_html($k) . '</option>';
}
?>
                    </select>
                    <form class="ppcart-reports-filter" method="get">
                        <input type="hidden" name="page" value="<?php echo esc_attr(PPCart_Admin_Screens::PAGE_REPORTS); ?>" />
                        <label class="ppcart-reports-filter-field">
                            <span class="screen-reader-text"><?php esc_html_e('Date range', 'publishpress-cart'); ?></span>
                            <input class="ppcart-date-range-input" type="text" name="date" id="reports-range" value="<?php echo esc_attr($date); ?>" autocomplete="off" placeholder="<?php echo esc_attr__('Select date range', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-reports-date-range')); ?>" />
                        </label>
                        <div class="ppcart-reports-filter-field ppcart-reports-customer-search">
                            <label class="screen-reader-text" for="ppcart-reports-customer-search"><?php esc_html_e('Search customers by name or email', 'publishpress-cart'); ?></label>
                            <input type="hidden" name="customer" class="ppcart-reports-customer-value" value="<?php echo esc_attr($report_filters['customer']); ?>" />
                            <input
                                id="ppcart-reports-customer-search"
                                class="ppcart-reports-customer-input"
                                type="search"
                                value="<?php echo esc_attr($customer_search_value); ?>"
                                placeholder="<?php echo esc_attr__('Search by name or email', 'publishpress-cart'); ?>"
                                autocomplete="off"
                                spellcheck="false"
                                aria-autocomplete="list"
                                aria-controls="ppcart-reports-customer-results"
                                data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-reports-customer')); ?>"
                            />
                            <ul id="ppcart-reports-customer-results" class="ppcart-reports-search-results ppcart-reports-customer-results" hidden></ul>
                        </div>
                        <div class="ppcart-reports-filter-field ppcart-reports-product-search">
                            <label class="screen-reader-text" for="ppcart-reports-product-search"><?php esc_html_e('Search products', 'publishpress-cart'); ?></label>
                            <input type="hidden" name="product_id" class="ppcart-reports-product-value" value="<?php echo esc_attr($report_filters['product_id'] > 0 ? (string) $report_filters['product_id'] : ''); ?>" />
                            <input
                                id="ppcart-reports-product-search"
                                class="ppcart-reports-product-input"
                                type="search"
                                value="<?php echo esc_attr($product_search_value); ?>"
                                placeholder="<?php echo esc_attr__('All products', 'publishpress-cart'); ?>"
                                autocomplete="off"
                                spellcheck="false"
                                aria-autocomplete="list"
                                aria-controls="ppcart-reports-product-results"
                                title="<?php echo esc_attr__('Filters by the order or subscription\'s primary product', 'publishpress-cart'); ?>"
                                data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-reports-product')); ?>"
                            />
                            <ul id="ppcart-reports-product-results" class="ppcart-reports-search-results ppcart-reports-product-results" hidden></ul>
                        </div>
                        <input class="button button-primary" type="submit" value="<?php echo esc_attr__('Apply', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-reports-apply')); ?>" />
                        <a class="button ppcart-reports-clear" href="<?php echo esc_url($clear_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-reports-clear')); ?>"><?php esc_html_e('Clear filters', 'publishpress-cart'); ?></a>
                        <a id="customer_csv_export__" class="button customer_csv_export" href="<?php echo esc_url(wp_nonce_url(add_query_arg($export_query, home_url('/')), 'ppcart_csv_export')); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-reports-export-orders')); ?>"><?php esc_html_e('Export Orders', 'publishpress-cart'); ?></a>
                    </form>
                </div>
                <p class="ppcart-reports-filter-hint">
                    <?php esc_html_e('The product filter matches the primary product on each order or subscription, not bump or upsell line items.', 'publishpress-cart'); ?>
                </p>

                <?php
                $argsfunded = [
                    'post_type'  => ppcart_query_post_types('order'),
                    'post_status' => 'refunded',
                    // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
                    'posts_per_page' => -1,
                ];


$args = [
    'post_type'  => ppcart_query_post_types('order'),
    'post_status' => 'paid',
    // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
    'posts_per_page' => -1,
];

$date_query = $this->get_report_date_query($report_period);

if (! empty($date_query)) {
    $args['date_query']       = $date_query;
    $argsfunded['date_query'] = $date_query;
}

$args       = ppcart_apply_report_filters_to_query_args($args, $report_filters);
$argsfunded = ppcart_apply_report_filters_to_query_args($argsfunded, $report_filters);

$carttotal = ['total' => 0];
$products = [];
$main = ['total' => 0];
$upsells = ['total' => 0];
$downsells = ['total' => 0];
$bumps = ['total' => 0];
$customers = [];
$gateways = [];
$coupons = [];
$product_revenue = [];
$revenue_events = [];
$renewal_orders = 0;
$new_orders = 0;

$queryrfunded = new WP_Query($argsfunded);
$refunds_array = [];
$refunded_amount_array = [];
$refunds_time_array = [];
if ($queryrfunded->have_posts()) {
    while ($queryrfunded->have_posts()) {
        $queryrfunded->the_post();
        $postid = get_the_ID();
        $pid = ppcart_get_post_meta(get_the_ID(), 'product_id', true);
        $refunds_array[$pid][] = get_the_title($pid);

        $refund_data = $this->get_refund_data($postid);
        $refunds_time_array[] = $refund_data['count'];
        $refunded_amount_array[] = $refund_data['amount'];
    }
}
$refunded_amount = array_sum($refunded_amount_array);
$refunded_time = array_sum($refunds_time_array);

$query = new WP_Query($args);
if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $cart_order = new PPCart_Order(get_the_ID());

        if (ppcart_get_post_meta(get_the_ID(), 'renewal_order', true)) {
            $renewal_orders++;
        } else {
            $new_orders++;
        }

        $amount = (float) $cart_order->amount;
        $post_datetime = function_exists('get_post_datetime') ? get_post_datetime(get_the_ID()) : false;
        $order_timestamp = $post_datetime instanceof DateTimeInterface ? $post_datetime->getTimestamp() : $this->get_report_timestamp(get_the_date('Y-m-d H:i:s'));
        $carttotal['total'] += (is_numeric($amount)) ? $amount : 0;
        $revenue_events[] = [
            'date'      => get_the_date('Y-m-d'),
            'timestamp' => $order_timestamp,
            'amount'    => $amount,
        ];

        if ($pid = $cart_order->product_id) {
            if (!isset($products[$pid])) {
                $products[$pid] = 0;
            }
            $products[$pid]++;
            if (!isset($product_revenue[$pid])) {
                $product_revenue[$pid] = 0;
            }
            $product_revenue[$pid] += $amount;

            $orderEmail = $cart_order->email;
            if ($orderEmail && !in_array($orderEmail, $customers)) {
                $customers[] = $orderEmail;
            }

            $method = $cart_order->pay_method;
            if (!$method) {
                $method = 'stripe';
            }

            // payment method
            if (!isset($gateways[$method])) {
                $gateways[$method] = 0;
            }
            $gateways[$method]++;

            // coupon
            if ($coupon = $cart_order->coupon) {
                if (is_array($coupon)) {
                    $coupon = $cart_order->coupon_id;
                }
                if ($coupon) {
                    if (!isset($coupons[$pid][$coupon])) {
                        $coupons[$pid][$coupon] = 0;
                    }
                    $coupons[$pid][$coupon]++;
                }
            }

            if ($usp = $cart_order->us_parent) {
                if (!isset($upsells[$pid])) {
                    $upsells[$pid] = 0;
                }
                $upsells[$pid]++;
                $upsells['total']++;

                // payment method
                if (!isset($upsells['gateways'][$method])) {
                    $upsells['gateways'][$method] = 0;
                }
                $upsells['gateways'][$method]++;

                if (!isset($carttotal[$usp])) {
                    $carttotal[$usp] = 0;
                }
                $carttotal[$usp] += $amount;
            } elseif ($dsp = $cart_order->ds_parent) {
                if (!isset($downsells[$pid])) {
                    $downsells[$pid] = 0;
                }
                $downsells[$pid]++;
                $downsells['total']++;

                // payment method
                if (!isset($downsells['gateways'][$method])) {
                    $downsells['gateways'][$method] = 0;
                }
                $downsells['gateways'][$method]++;

                if (!isset($carttotal[$dsp])) {
                    $carttotal[$dsp] = 0;
                }
                $carttotal[$dsp] += $amount;
            } elseif (isset($cart_order->ob_parent) && $obp = $cart_order->ob_parent) { // deprecated
                if (!isset($bumps[$pid])) {
                    $bumps[$pid] = 0;
                }
                $bumps[$pid]++;
                $bumps['total']++;

                if (!isset($carttotal[$obp])) {
                    $carttotal[$obp] = 0;
                }
                $carttotal[$obp] += $amount;
            } else {
                if (!isset($main[$pid])) {
                    $main[$pid] = 0;
                }
                $main[$pid]++;
                $main['total']++;

                // payment method
                if (!isset($main['gateways'][$pid][$method])) {
                    $main['gateways'][$pid][$method] = 0;
                }
                $main['gateways'][$pid][$method]++;

                if (!isset($carttotal[get_the_ID()])) {
                    $carttotal[get_the_ID()] = 0;
                }
                $carttotal[get_the_ID()] += $amount;
            }

            if (isset($cart_order->bump_id) && ($ob = $cart_order->bump_id) && is_int($ob)) { // backwards compatibility
                if (!isset($products[$ob])) {
                    $products[$ob] = 0;
                }
                $products[$ob]++;

                if (!isset($bumps[$ob])) {
                    $bumps[$ob] = 0;
                }

                $bumps[$ob]++;
                $bumps['total']++;
            }

            if (is_countable($cart_order->order_bumps)) {
                foreach ($cart_order->order_bumps as $bump) {
                    $ob = $bump['id'];
                    if (!isset($products[$ob])) {
                        $products[$ob] = 0;
                    }
                    $products[$ob]++;

                    if (!isset($bumps[$ob])) {
                        $bumps[$ob] = 0;
                    }

                    $bumps[$ob]++;
                    $bumps['total']++;
                }
            }
        }
    }
}
wp_reset_postdata();

$gatewayLabels = [
    'paypal' => 'PayPal',
    'stripe' => 'Stripe',
    'cod'    => __('Cash on Delivery', 'publishpress-cart'),
    'free'   => __('Free', 'publishpress-cart'),
];
$orders_count = (int) $query->found_posts;
$net_sales = max(0, $carttotal['total'] - $refunded_amount);
$avg_amt = ($orders_count > 0) ? $carttotal['total'] / $orders_count : 0;
$period_customer_value = count($customers) ? $net_sales / count($customers) : 0;
$lifetime_value = $this->get_customer_lifetime_value(ppcart_get_report_identity_query_args($report_filters));
$subscription_metrics = $this->get_subscription_metrics($report_period, ppcart_get_report_meta_query($report_filters));
$revenue_buckets = $this->build_revenue_buckets($revenue_events, $report_period);
$product_revenue_rows = $this->get_product_revenue_rows($product_revenue);
$gateway_rows = $this->get_gateway_rows($gateways, $gatewayLabels);
$churn_context = ! empty($report_period['is_all_time'])
    ? sprintf(
        /* translators: %s: canceled subscription count. */
        __('%s cancellations in local records', 'publishpress-cart'),
        number_format_i18n($subscription_metrics['canceled'])
    )
    : sprintf(
        /* translators: 1: canceled subscription count, 2: active subscriptions at period start. */
        __('%1$s cancellations; %2$s active at period start', 'publishpress-cart'),
        number_format_i18n($subscription_metrics['canceled']),
        number_format_i18n($subscription_metrics['active_at_start'])
    );
?>

                <section class="ppcart-reports-overview" aria-label="<?php esc_attr_e('Report overview', 'publishpress-cart'); ?>">
                    <?php
    $this->render_metric_card(
        __('Net Revenue', 'publishpress-cart'),
        ppcart_format_price($net_sales),
        sprintf(
            /* translators: %s: gross revenue. */
            __('Gross %s before refunds', 'publishpress-cart'),
            wp_strip_all_tags(ppcart_format_price($carttotal['total']))
        ),
        'is-primary'
    );
$this->render_metric_card(
    __('Customer Lifetime Value', 'publishpress-cart'),
    ppcart_format_price($lifetime_value['value']),
    ppcart_report_identity_filters_are_active($report_filters)
        ? sprintf(
            /* translators: %s: customer count. */
            __('All-time average across %s matching customers', 'publishpress-cart'),
            number_format_i18n($lifetime_value['customers'])
        )
        : sprintf(
            /* translators: %s: customer count. */
            __('All-time average across %s customers', 'publishpress-cart'),
            number_format_i18n($lifetime_value['customers'])
        )
);
$this->render_metric_card(
    __('MRR', 'publishpress-cart'),
    ppcart_format_price($subscription_metrics['mrr']),
    sprintf(
        /* translators: %s: recurring subscription count. */
        __('%s active recurring subscriptions', 'publishpress-cart'),
        number_format_i18n($subscription_metrics['recurring'])
    )
);
$this->render_metric_card(
    __('Churn Rate', 'publishpress-cart'),
    number_format_i18n($subscription_metrics['churn_rate'], 1) . '%',
    $churn_context
);
$this->render_metric_card(
    __('Transactions', 'publishpress-cart'),
    number_format_i18n($orders_count),
    $renewal_orders > 0
        ? sprintf(
            /* translators: 1: new order count, 2: renewal order count. */
            __('%1$s new, %2$s renewals', 'publishpress-cart'),
            number_format_i18n($new_orders),
            number_format_i18n($renewal_orders)
        )
        : __('Paid orders and post-purchase offers', 'publishpress-cart')
);
$this->render_metric_card(
    __('Avg Order Value', 'publishpress-cart'),
    ppcart_format_price($avg_amt),
    __('Based on paid transactions', 'publishpress-cart')
);
?>
                </section>

                <section class="ppcart-reports-insights">
                    <article class="ppcart-report-panel ppcart-report-panel--wide">
                        <div class="ppcart-report-panel__header">
                            <h3><?php esc_html_e('Revenue Trend', 'publishpress-cart'); ?></h3>
                            <span><?php echo esc_html($report_period['label']); ?></span>
                        </div>
                        <?php $this->render_revenue_chart($revenue_buckets); ?>
                    </article>

                    <article class="ppcart-report-panel">
                        <div class="ppcart-report-panel__header">
                            <h3><?php esc_html_e('Subscription Health', 'publishpress-cart'); ?></h3>
                            <span><?php esc_html_e('Local subscription records', 'publishpress-cart'); ?></span>
                        </div>
                        <div class="ppcart-report-health">
                            <div>
                                <span><?php esc_html_e('Active', 'publishpress-cart'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n($subscription_metrics['active'])); ?></strong>
                            </div>
                            <div>
                                <span><?php esc_html_e('New', 'publishpress-cart'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n($subscription_metrics['new'])); ?></strong>
                            </div>
                            <div>
                                <span><?php esc_html_e('Canceled', 'publishpress-cart'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n($subscription_metrics['canceled'])); ?></strong>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="ppcart-reports-breakdowns">
                    <article class="ppcart-report-panel">
                        <div class="ppcart-report-panel__header">
                            <h3><?php esc_html_e('Top Products by Revenue', 'publishpress-cart'); ?></h3>
                            <span><?php esc_html_e('Paid order amount', 'publishpress-cart'); ?></span>
                        </div>
                        <?php $this->render_bar_rows($product_revenue_rows, __('No paid product revenue found for this period.', 'publishpress-cart')); ?>
                    </article>

                    <article class="ppcart-report-panel">
                        <div class="ppcart-report-panel__header">
                            <h3><?php esc_html_e('Payment Method Mix', 'publishpress-cart'); ?></h3>
                            <span><?php esc_html_e('Transactions by gateway', 'publishpress-cart'); ?></span>
                        </div>
                        <?php $this->render_bar_rows($gateway_rows, __('No payment method data found for this period.', 'publishpress-cart')); ?>
                    </article>
                </section>

                <h3 class="ppcart-report-section-title"><?php esc_html_e('Detailed order breakdown', 'publishpress-cart'); ?></h3>
                <div class="totals" style="flex-wrap: wrap;">


                    <div class="total-sales column">
                        <div class="postbox">
                            <p><?php esc_html_e('Total Sales', 'publishpress-cart'); ?></p>
                            <h3><?php ppcart_formatted_price($carttotal['total']); ?></h3>
                        </div>
                    </div>

                    <div class="total-sales column">
                        <div class="postbox">
                            <p><?php esc_html_e('Net Sales', 'publishpress-cart'); ?></p>
                            <h3><?php ppcart_formatted_price($carttotal['total'] - $refunded_amount); ?></h3>
                        </div>
                    </div>

                    <div class="total-downsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Total Refunds', 'publishpress-cart'); ?></p>
                            <h3><?php echo esc_html($refunded_time); ?></h3>
                        </div>
                    </div>

                    <div class="total-downsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Amount Refunded', 'publishpress-cart'); ?></p>
                            <h3><?php ppcart_formatted_price($refunded_amount); ?></h3>
                        </div>
                    </div>

                    <div class="total-upsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Avg Revenue per Customer', 'publishpress-cart'); ?></p>
                            <h3><?php ppcart_formatted_price($period_customer_value); ?></h3>
                        </div>
                    </div>

                    <div class="total-orders column">
                        <div class="postbox">
                            <p><?php echo esc_html(apply_filters('ppcart_admin_reports_transactions_label', __('Transactions', 'publishpress-cart'))); ?></p>
                            <h3><?php echo esc_html($query->found_posts); ?></h3>
                        </div>
                    </div>

                    <div class="total-upsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Customers', 'publishpress-cart'); ?></p>
                            <h3><?php echo esc_html(count($customers)); ?></h3>
                        </div>
                    </div>

                    <?php
do_action(
    'ppcart_admin_reports_summary_columns',
    [
        'bumps'     => $bumps,
        'upsells'   => $upsells,
        'downsells' => $downsells,
    ]
);
?>

                </div>
                <div class="products">
                    <div class="product-list">
                        <h3><?php esc_html_e('Sales by Product', 'publishpress-cart'); ?></h3>
                        <?php
    if (empty($products)) {
        $this->render_empty_state(
            __('No product sales in this period', 'publishpress-cart'),
            __('Completed paid orders will appear here once customers buy a product in the selected date range.', 'publishpress-cart')
        );
    } else {
        foreach ($products as $k => $v) {
            echo '<div class="product-row"><span class="product-title">' . esc_html(get_the_title($k)) . '</span> <span class="num">' . esc_html($v) . '</span></div>';
        }
    }
?>
                    </div>

                    <div class="product-list">
                        <h3><?php esc_html_e('Refunds by Product', 'publishpress-cart'); ?></h3>
                        <?php
if (empty($refunds_array)) {
    $this->render_empty_state(
        __('No product refunds in this period', 'publishpress-cart'),
        __('Refunded products will appear here when an order refund is recorded for the selected date range.', 'publishpress-cart')
    );
} else {
    foreach ($refunds_array as $k => $v) {
        echo '<div class="product-row"><span class="product-title">' . esc_html(get_the_title($k)) . '</span> <span class="num">' . esc_html(count($v)) . '</span></div>';
    }
}
?>
                    </div>

                    <div class="product-list">
                        <h3><?php esc_html_e('Transactions by Payment Gateway', 'publishpress-cart'); ?></h3>
                        <?php
if (empty($gateways)) {
    $this->render_empty_state(
        __('No gateway transactions in this period', 'publishpress-cart'),
        __('Paid transactions will be grouped by gateway here after checkout activity is recorded.', 'publishpress-cart')
    );
} else {
    foreach ($gateways as $k => $v) {
        $gateway_label = $gatewayLabels[$k] ?? $k;
        echo '<div class="product-row"><span class="product-title">' . esc_html($gateway_label) . '</span> <span class="num">' . esc_html($v) . '</span></div>';
    }
}
?>
                    </div>

                    <?php if (!empty($coupons)) : ?>
                        <div class="product-list">
                            <h3>Completed Orders by Coupon Code</h3>
                            <?php
    foreach ($coupons as $prod => $coupons) {
        echo '<div class="product-row"><span class="product-title">' . esc_html(get_the_title($prod)) . '</span></div>';
        foreach ($coupons as $k => $v) {
            echo '<div class="product-row"><span class="product-title">' . esc_html($k) . '</span> <span class="num">' . esc_html($v) . '</span></div>';
        }
    }
                        ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($renewal_orders > 0) : ?>
                        <div class="product-list">
                            <h3><?php esc_html_e('Orders by Type', 'publishpress-cart'); ?></h3>
                            <div class="product-row">
                                <span class="product-title"><?php esc_html_e('New', 'publishpress-cart'); ?></span>
                                <span class="num"><?php echo esc_html(number_format_i18n($new_orders)); ?></span>
                            </div>
                            <div class="product-row">
                                <span class="product-title"><?php esc_html_e('Renewals', 'publishpress-cart'); ?></span>
                                <span class="num"><?php echo esc_html(number_format_i18n($renewal_orders)); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="product-list">
                        <h3><?php esc_html_e('Main Orders by Product', 'publishpress-cart'); ?></h3>
                        <?php
                        $has_main_orders = false;
foreach ($main as $k => $v) {
    if ($k == 'total') {
        continue;
    }

    if ($k != 'gateways') {
        $has_main_orders = true;
        echo '<div class="product-row"><span class="product-title">' . esc_html(get_the_title($k)) . '</span> <span class="num">' . esc_html($v) . '</span></div>';
        foreach ($main['gateways'][$k] as $gw => $gwa) {
            $gateway_label = $gatewayLabels[$gw] ?? $gw;
            echo esc_html($gateway_label) . ': ' . esc_html($gwa) . '<br>';
        }
    }
}

if (! $has_main_orders) {
    $this->render_empty_state(
        __('No main orders in this period', 'publishpress-cart'),
        __('Main product order counts will appear here once paid checkout orders are tracked.', 'publishpress-cart')
    );
}
?>
                    </div>

                    <?php
                    do_action(
                        'ppcart_admin_reports_product_columns',
                        [
                            'bumps'          => $bumps,
                            'upsells'        => $upsells,
                            'downsells'      => $downsells,
                            'gateway_labels' => $gatewayLabels,
                        ]
                    );
?>
                </div>
            </div>
        </div>
        <?php if (function_exists('ppcart_render_admin_sidebar')) {
            ppcart_render_admin_sidebar();
        } ?>
    </div>
</div><!-- /.wrap -->
<?php
