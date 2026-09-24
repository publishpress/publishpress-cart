<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ppcart-customer-report-page">
    <h1 class="screen-reader-text">
        <?php
        printf(
            /* translators: %s: customer name. */
            esc_html__('Customer report for %s', 'publishpress-cart'),
            esc_html($customer_name)
        );
?>
    </h1>

    <div class="ppcart-customer-header">
        <div class="ppcart-customer-actions">
            <a class="button" href="<?php echo esc_url($contacts_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-back-to-contacts')); ?>"><?php esc_html_e('Back to Contacts', 'publishpress-cart'); ?></a>
            <a class="button" href="<?php echo esc_url($orders_list_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-view-orders')); ?>"><?php esc_html_e('View Orders', 'publishpress-cart'); ?></a>
            <a id="customer_csv_export__" class="button button-primary customer_csv_export" href="<?php echo esc_url($export_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-export')); ?>"><?php echo esc_html($export_label); ?></a>
        </div>
    </div>

    <?php
    ob_start();
do_action('ppcart_customer_report_admin_notices');
$customer_notices = trim((string) ob_get_clean());
if ('' !== $customer_notices) :
    ?>
        <div class="ppcart-customer-notices">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Captured notices are already rendered by WordPress/admin notice callbacks.
            echo $customer_notices;
    ?>
        </div>
<?php endif; ?>

    <div class="ppcart-customer-toolbar">
        <div>
            <strong><?php esc_html_e('Customer activity', 'publishpress-cart'); ?></strong>
            <span><?php echo esc_html($date_label); ?></span>
        </div>
        <form class="ppcart-customer-date-form">
            <input type="hidden" name="page" value="<?php echo esc_attr(PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS); ?>" />
            <input type="hidden" id="customer_emailid" name="customerid" value="<?php echo esc_attr($customer); ?>" />
            <input type="hidden" name="reportstypes" value="<?php echo esc_attr($report_type); ?>" />
            <label class="screen-reader-text" for="reports-range"><?php esc_html_e('Report date range', 'publishpress-cart'); ?></label>
            <input type="text" name="date" id="reports-range" value="<?php echo esc_attr($date); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-date-range')); ?>" />
            <input type="submit" value="<?php echo esc_attr__('Apply', 'publishpress-cart'); ?>" class="button button-primary" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-apply')); ?>" />
        </form>
    </div>

    <div class="ppcart-customer-metrics" aria-label="<?php esc_attr_e('Customer summary', 'publishpress-cart'); ?>">
        <div class="ppcart-customer-metric">
            <span><?php esc_html_e('Net Revenue', 'publishpress-cart'); ?></span>
            <strong><?php echo wp_kses_post(ppcart_format_price($net_amount)); ?></strong>
        </div>
        <div class="ppcart-customer-metric">
            <span><?php esc_html_e('LTV', 'publishpress-cart'); ?></span>
            <strong><?php echo wp_kses_post(ppcart_format_price($lifetime_value)); ?></strong>
        </div>
        <div class="ppcart-customer-metric">
            <span><?php esc_html_e('Paid Orders', 'publishpress-cart'); ?></span>
            <strong><?php echo esc_html(number_format_i18n($paid_order_count)); ?></strong>
        </div>
        <div class="ppcart-customer-metric">
            <span><?php esc_html_e('Average Order', 'publishpress-cart'); ?></span>
            <strong><?php echo wp_kses_post(ppcart_format_price($average_order_amount)); ?></strong>
        </div>
        <div class="ppcart-customer-metric">
            <span><?php esc_html_e('Refunds', 'publishpress-cart'); ?></span>
            <strong><?php echo wp_kses_post(ppcart_format_price($refunded_amount)); ?></strong>
        </div>
        <div class="ppcart-customer-metric">
            <span><?php esc_html_e('Active Subscriptions', 'publishpress-cart'); ?></span>
            <strong><?php echo esc_html(number_format_i18n($active_subscription_count)); ?></strong>
        </div>
    </div>

    <div class="ppcart-customer-layout">
        <main class="ppcart-customer-main">
            <nav class="ppcart-customer-tabs" aria-label="<?php esc_attr_e('Customer report tabs', 'publishpress-cart'); ?>">
                <a href="<?php echo esc_url($orders_url); ?>" class="<?php echo 'order' === $report_type ? 'is-active' : ''; ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-tab-orders')); ?>">
                    <?php esc_html_e('Orders', 'publishpress-cart'); ?>
                    <span><?php echo esc_html(number_format_i18n($order_count)); ?></span>
                </a>
                <a href="<?php echo esc_url($subscriptions_url); ?>" class="<?php echo 'subscription' === $report_type ? 'is-active' : ''; ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-tab-subscriptions')); ?>">
                    <?php esc_html_e('Subscriptions', 'publishpress-cart'); ?>
                    <span><?php echo esc_html(number_format_i18n($subscription_count)); ?></span>
                </a>
            </nav>

            <?php if ('order' === $report_type) : ?>
                <section class="ppcart-customer-panel">
                    <div class="ppcart-customer-panel__header">
                        <div>
                            <h2><?php esc_html_e('Order Timeline', 'publishpress-cart'); ?></h2>
                            <p><?php esc_html_e('Paid, refunded, upsell, downsell, and bump activity for the selected period.', 'publishpress-cart'); ?></p>
                        </div>
                        <a class="button" href="<?php echo esc_url($orders_list_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-open-orders-list')); ?>"><?php esc_html_e('Open Orders List', 'publishpress-cart'); ?></a>
                    </div>
                    <?php if (! empty($order_rows)) : ?>
                        <div class="ppcart-customer-timeline">
                            <?php
                    $current_date = '';
                        foreach ($order_rows as $order_row) :
                            if ($current_date !== $order_row['date']) :
                                $current_date = $order_row['date'];
                                ?>
                                    <div class="ppcart-customer-timeline__date"><?php echo esc_html($current_date); ?></div>
                            <?php endif; ?>
                                <article class="ppcart-customer-order-card <?php echo $order_row['is_refunded'] ? 'is-refunded' : 'is-paid'; ?>">
                                    <div class="ppcart-customer-order-card__status" aria-hidden="true">
                                        <span class="dashicons <?php echo $order_row['is_refunded'] ? 'dashicons-image-rotate' : 'dashicons-saved'; ?>"></span>
                                    </div>
                                    <div class="ppcart-customer-order-card__body">
                                        <h3>
                                            <a href="<?php echo esc_url($order_row['edit_url']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-order-' . $order_row['id'] . '-product')); ?>"><?php echo esc_html($order_row['product_title']); ?></a>
                                        </h3>
                                        <div class="ppcart-customer-order-meta">
                                            <?php if ('' !== $order_row['type_label']) : ?>
                                                <span class="order-plan <?php echo esc_attr($order_row['type_class']); ?>"><?php echo esc_html($order_row['type_label']); ?></span>
                                            <?php endif; ?>
                                            <span class="ppcart-customer-status <?php echo esc_attr(sanitize_html_class($order_row['status'])); ?>"><?php echo esc_html($order_row['status_label']); ?></span>
                                            <a href="<?php echo esc_url($order_row['edit_url']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-order-' . $order_row['id'] . '-view')); ?>"><?php esc_html_e('View Order', 'publishpress-cart'); ?></a>
                                        </div>
                                    </div>
                                    <div class="ppcart-customer-order-card__amount"><?php echo wp_kses_post($order_row['amount_html']); ?></div>
                                </article>
                        <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="ppcart-customer-empty">
                            <h3><?php esc_html_e('No orders in this date range', 'publishpress-cart'); ?></h3>
                            <p><?php esc_html_e('Try a broader date range or open the full orders list for this customer.', 'publishpress-cart'); ?></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ('subscription' === $report_type) : ?>
                <section class="ppcart-customer-panel">
                    <div class="ppcart-customer-panel__header">
                        <div>
                            <h2><?php esc_html_e('Subscriptions', 'publishpress-cart'); ?></h2>
                            <p><?php esc_html_e('Recurring plans, status, collected revenue, and remaining payments for this customer.', 'publishpress-cart'); ?></p>
                        </div>
                        <a class="button" href="<?php echo esc_url($subscriptions_list_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-open-subscriptions-list')); ?>"><?php esc_html_e('Open Subscriptions List', 'publishpress-cart'); ?></a>
                    </div>
                    <?php if (! empty($subscription_rows)) : ?>
                        <div class="ppcart-customer-table-wrap">
                            <table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed striped table-view-list ppcart-customer-subscriptions" width="100%">
                                <thead>
                                    <tr>
                                        <th class="column-date"><?php esc_html_e('Date', 'publishpress-cart'); ?></th>
                                        <th class="column-product"><?php esc_html_e('Product', 'publishpress-cart'); ?></th>
                                        <th class="column-plan"><?php esc_html_e('Plan', 'publishpress-cart'); ?></th>
                                        <th class="column-interval"><?php esc_html_e('Pay Interval', 'publishpress-cart'); ?></th>
                                        <th class="column-status"><?php esc_html_e('Status', 'publishpress-cart'); ?></th>
                                        <th class="column-revenue"><?php esc_html_e('Total Revenue', 'publishpress-cart'); ?></th>
                                        <th class="column-remaining"><?php esc_html_e('Remaining Payments', 'publishpress-cart'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="order_line_items">
                                    <?php foreach ($subscription_rows as $subscription_row) : ?>
                                        <tr>
                                            <td class="column-date"><a href="<?php echo esc_url($subscription_row['edit_url']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-subscription-' . $subscription_row['id'] . '-view')); ?>"><?php echo esc_html($subscription_row['date']); ?></a></td>
                                            <td class="column-product"><?php echo esc_html($subscription_row['product_title']); ?></td>
                                            <td class="column-plan"><?php echo esc_html($subscription_row['plan']); ?></td>
                                            <td class="column-interval"><?php echo wp_kses_post($subscription_row['pay_interval']); ?></td>
                                            <td class="column-status"><span class="ppcart-customer-status <?php echo esc_attr(sanitize_html_class($subscription_row['status'])); ?>"><?php echo esc_html($subscription_row['status_label']); ?></span></td>
                                            <td class="column-revenue"><?php echo wp_kses_post($subscription_row['total_revenue_html']); ?></td>
                                            <td class="column-remaining"><?php echo wp_kses_post($subscription_row['payments_remaining']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="ppcart-customer-empty">
                            <h3><?php esc_html_e('No subscriptions in this date range', 'publishpress-cart'); ?></h3>
                            <p><?php esc_html_e('Try a broader date range or open the full subscriptions list for this customer.', 'publishpress-cart'); ?></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>

        <aside class="ppcart-customer-aside" aria-label="<?php esc_attr_e('Customer details', 'publishpress-cart'); ?>">
            <section class="ppcart-customer-side-card">
                <h2><?php esc_html_e('Customer Details', 'publishpress-cart'); ?></h2>
                <div class="ppcart-customer-profile">
                    <?php echo get_avatar($customer, 56); ?>
                    <div>
                        <strong><?php echo esc_html($customer_name); ?></strong>
                        <a href="mailto:<?php echo esc_attr($customer); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-email')); ?>"><?php echo esc_html($customer); ?></a>
                    </div>
                </div>
                <dl>
                    <div>
                        <dt><?php esc_html_e('Phone', 'publishpress-cart'); ?></dt>
                        <dd><?php echo $customer_phone ? esc_html($customer_phone) : '<span class="ppcart-customer-empty-value">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Location', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($location_parts) ? esc_html(implode(', ', $location_parts)) : '<span class="ppcart-customer-empty-value">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('WordPress Account', 'publishpress-cart'); ?></dt>
                        <dd>
                            <?php if ($customer_user) : ?>
                                <a href="<?php echo esc_url(get_edit_user_link($customer_user->ID)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-customer-report-user-profile')); ?>"><?php echo esc_html('#' . $customer_user->ID . ' ' . $customer_user->user_login); ?></a>
                            <?php else : ?>
                                <span class="ppcart-customer-empty-value"><?php esc_html_e('Guest checkout', 'publishpress-cart'); ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Customer ID', 'publishpress-cart'); ?></dt>
                        <dd><?php echo $customer_account ? esc_html($customer_account) : '<span class="ppcart-customer-empty-value">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Last Gateway', 'publishpress-cart'); ?></dt>
                        <dd><?php echo $customer_gateway ? esc_html(ucwords(str_replace('_', ' ', $customer_gateway))) : '<span class="ppcart-customer-empty-value">&mdash;</span>'; ?></dd>
                    </div>
                </dl>
            </section>

            <section class="ppcart-customer-side-card">
                <h2><?php esc_html_e('Customer History', 'publishpress-cart'); ?></h2>
                <dl>
                    <div>
                        <dt><?php esc_html_e('First Order', 'publishpress-cart'); ?></dt>
                        <dd><?php echo $first_order_date ? esc_html($first_order_date) : '<span class="ppcart-customer-empty-value">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Last Order', 'publishpress-cart'); ?></dt>
                        <dd><?php echo $last_order_date ? esc_html($last_order_date) : '<span class="ppcart-customer-empty-value">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Products Purchased', 'publishpress-cart'); ?></dt>
                        <dd><?php echo esc_html(number_format_i18n(count($product_ids))); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Subscription Revenue', 'publishpress-cart'); ?></dt>
                        <dd><?php echo wp_kses_post(ppcart_format_price($subscription_revenue)); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Next Payment', 'publishpress-cart'); ?></dt>
                        <dd><?php echo $next_payment_date ? esc_html($next_payment_date) : '<span class="ppcart-customer-empty-value">&mdash;</span>'; ?></dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</div>
