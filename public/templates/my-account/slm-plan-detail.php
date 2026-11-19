<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-my-account ppcart-my-subscription-page">
    <?php if ($account_page_id = get_option('_ppcart_myaccount_page_id')) : ?>
    <div class="back-btn">
        <a href="<?php echo esc_url(get_permalink($account_page_id)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-plan-detail-back')); ?>"><img src="<?php echo esc_url(PPCART_BASE_URL . 'public/images/arrow-back.svg'); ?>" alt="Icon" /> <?php esc_html_e('Back', 'publishpress-cart'); ?></a>
    </div>
    <?php endif; ?>
    <div class="ppcart-account-subscription">
        <?php
        $ppcart_slm_order = ppcart_filter_input_request('ppcart-slm-order', FILTER_VALIDATE_INT);
if (false !== $ppcart_slm_order && null !== $ppcart_slm_order) {
    $order_post_id = absint($ppcart_slm_order);
    $ppcart_order = new PPCart_Order($order_post_id);
    $order_data = $ppcart_order->get_data();
    $subscription_id = $order_data['subscription_id'];

    $attr = ppcart_get_item_list($order_post_id, false);
    ?>

            <div id="ppcart-order-details" class="ppcart-content-inner">
                <div class="ppcart-order-header">
                    <h3 class="ppcart-heading"><?php esc_html_e("Plan Details", "publishpress-cart"); ?></h3>
                    <div class="plan-btn-group">
                        <a href="#" class="plan-btn" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-plan-detail-change-plan')); ?>">Change plan</a>
                        <?php
                    $additionalParam = "";
    $customer_portal = get_option('_ppcart_stripe_customer_portal_enable');
    if ($order_data['pay_method'] == 'stripe' && $customer_portal) {
        $additionalParam = "&ppcart-manage=stripe";
    }
    ?>
                        <a href="<?php echo esc_url(add_query_arg('ppcart-plan', absint($subscription_id)) . $additionalParam); ?>" class="manage-btn" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-' . $subscription_id . '-manage')); ?>">Manage subscription</a>
                    </div>
                </div>
                <div class="ppcart-order-info">
                    <?php
    foreach ($attr['items'] as $item) {
        $subscription = new PPCart_Subscription($item['subscription_id']);
        if ($subscription) {
            $subscription_status = (in_array($subscription->status, ['pending-payment','initiated'])) ? 'pending' : $subscription->status;
            if ($subscription_status == 'completed' || $subscription_status == 'pending' || $subscription_status == 'canceled' || $subscription_status == 'paused') {
                $next = "--";
            } else {
                if ($subscription->sub_next_bill_date && !is_numeric($subscription->sub_next_bill_date)) {
                    $subscription->sub_next_bill_date = strtotime($subscription->sub_next_bill_date);
                }
                $next = date_i18n(get_option('date_format'), $subscription->sub_next_bill_date);
            }
            $next_date = ($subscription->cancel_date) ? '--' : $next;
            $renew_pan = $subscription->sub_payment;
            ?>
                                <div class="item">
                                    <div class="ppcart-plan-header">
                                        <div class="ppcart-plan-header-lhs">
                                            <h4><?php echo esc_html($subscription->product_name); ?> <?php echo isset($item['price_name']) ? ' - ' . esc_html($item['price_name']) : ''; ?></h4>
                                            <?php if (isset($item['sub_summary'])) : ?>
                                                <small><?php echo wp_kses_post($item['sub_summary']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ppcart-plan-header-rhs">
                                            <div class="plan-btn-group">
                                                <?php if ($subscription->status == 'incomplete' || $subscription->status == 'past_due' || $subscription->status == 'pending-payment') : ?>
                                                        <a href="<?php echo esc_url(add_query_arg(['ppcart-plan' => absint($subscription->ID), 'action' => 'pay'])); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-' . $subscription->ID . '-pay')); ?>"><?php esc_html_e('Pay', 'publishpress-cart'); ?></a>
                                                <?php  endif; ?>

                                                    <span class="ppcart-licence-status ppcart-licence-<?php echo esc_attr(strtolower($subscription->status)); ?>"><?php echo esc_html($subscription->status); ?></span>
                                                <?php if ($subscription->cancel_date && $subscription_status != 'canceled' && $next != '--') : ?>
                                                    <small><?php
                                                /* translators: %s: cancellation date. */
                                                printf(esc_html__('Cancels %s', 'publishpress-cart'), esc_html($next)); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (isset($subscription->amount)) : ?>
                                        <p><?php esc_html_e('Your next payment is', 'publishpress-cart'); ?> <?php echo wp_kses_post(ppcart_format_price($subscription->amount)); ?></p>
                                    <?php endif; ?>
                                    <p><?php esc_html_e('Your plan renews on', 'publishpress-cart'); ?> <?php echo esc_html($next_date); ?></p>
                                </div>
                            <?php
        }
    }
    ?>

                </div>
                <?php do_action('ppcart_receipt_after_order_details', $attr); ?>


            <?php
        if ($subscription_id) { ?>
                <div class="postbox">
                    <div class="ppcart-order-header">
                        <h4 class="ppcart-heading"><?php esc_html_e('Billing History', 'publishpress-cart'); ?></h4>
                    </div>

                    <table cellpadding="0" cellspacing="0" class="ppcart-order-items" width="100%">
                        <tbody id="order_line_items">
                        <?php

        $args = [
            'post_type' => ppcart_query_post_types('order'),
            'orderby' => 'date',
            'order'   => 'ASC',
            'post_status' => ['any'],
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Billing history must be filtered by subscription relation.
            'meta_query' => [
                [
                    'key' => ppcart_meta_key('subscription_id'),
                    'value' => $subscription_id,
                ],
            ],
        ];
            $the_query = new WP_Query($args);
            if ($the_query->have_posts()) {
                while ($the_query->have_posts()) {
                    $the_query->the_post();
                    $related_order = new PPCart_Order(get_the_ID());
                    $related_order = $related_order->get_data();
                    ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($related_order['invoice_link']); ?>" class="ppcart-order-item-name" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-order-' . get_the_ID() . '-invoice')); ?>" title="<?php esc_attr_e('Download Invoice', 'publishpress-cart'); ?>"><?php esc_html_e('Download Invoice', 'publishpress-cart'); ?></a>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="ppcart-order-item-name" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-order-' . get_the_ID() . '-admin-link')); ?>">#<?php echo esc_html(get_the_ID()); ?></a>
                                </td>
                                <td><?php echo esc_html(get_the_date('F j, Y', get_the_ID())); ?></td>
                                <td class="sub_cost">
                                    <div class="view">
                                        <span class="ppcart-Price-amount amount">
                                            <?php ppcart_formatted_price(ppcart_get_post_meta(get_the_ID(), 'amount', true)); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="name"><span class="ppcart-status ppcart-status-<?php echo esc_attr(strtolower($related_order['status'])); ?>"><?php echo esc_html($related_order['status']); ?></span></td>
                            </tr>
                            <?php
                     $initial_order = false;
                }
            }
            /* Restore original Post Data */
            wp_reset_postdata();
            ?>
                        </tbody>
                    </table>
                </div>
        <?php } ?>
            </div>

<?php } else {
    esc_html_e('No orders found.', 'publishpress-cart');
} ?>

    </div>
</div>
