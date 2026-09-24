<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-my-account ppcart-my-subscription-page">
    <?php if ($account_page_id = get_option('_ppcart_myaccount_page_id')) : ?>
    <div class="back-btn">
        <a href="<?php echo esc_url(get_permalink($account_page_id)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-back')); ?>"><img src="<?php echo esc_url(PPCART_BASE_URL . 'public/images/arrow-back.svg'); ?>" alt="Icon" /> <?php esc_html_e('Back', 'publishpress-cart'); ?></a>
    </div>
    <?php endif; ?>
    <div class="ppcart-account-subscription">
        <?php
        $ppcart_plan = ppcart_filter_input_request('ppcart-plan', FILTER_VALIDATE_INT);
if ((false === $ppcart_plan || null === $ppcart_plan) && ! empty($attr['plan'])) {
    $ppcart_plan = absint($attr['plan']);
}
if (false !== $ppcart_plan && null !== $ppcart_plan) {
    $subscription_post_id = absint($ppcart_plan);
    $sub = new PPCart_Subscription($subscription_post_id);
    $subscription_order = (object) $sub->get_data();

    if (ppcart_is_subscription_post_type(get_post_type($subscription_post_id))) {
        $is_cancellable = apply_filters('ppcart_is_sub_type_valid_for_cancel', $subscription_order->sub_installments == '-1', $subscription_order);
        $cancellable_statuses = apply_filters('ppcart_valid_sub_statuses_for_cancel', ['trialing','active','paused'], $subscription_order);
        $update_statuses = apply_filters('ppcart_valid_sub_statuses_for_update', ['trialing','active','paused', 'past_due', 'unpaid', 'incomplete'], $subscription_order);
        $show_cancel = ($is_cancellable && in_array($subscription_order->status, $cancellable_statuses) && !isset($subscription_order->cancel_request_date));

        $is_pausable = apply_filters('ppcart_is_sub_type_valid_for_pause_restart', $subscription_order->sub_installments == '-1', $subscription_order);
        $pausable_statuses = apply_filters('ppcart_valid_sub_statuses_for_pause_restart', ['trialing','active','paused'], $subscription_order);
        $show_pause = ($is_pausable && in_array($subscription_order->status, $pausable_statuses) && !isset($subscription_order->cancel_request_date));

        $product_id = $subscription_order->product_id;
        if (!is_numeric($subscription_order->sub_next_bill_date)) {
            $subscription_order->sub_next_bill_date = strtotime($subscription_order->sub_next_bill_date);
        }
        $next = date_i18n(get_option('date_format'), $subscription_order->sub_next_bill_date);
        ?>
                <div id="subscription-all" class="ppcart-content-inner">
                    <input type="hidden" id="ppcart_nonce" value="<?php echo esc_attr(wp_create_nonce('ppcart_ajax_nonce')); ?>">
                    <input type="hidden" name="ppcart_payment_intent" id="ppcart_payment_intent" value="<?php echo esc_attr($subscription_order->subscription_id); ?>">
                    <input type="hidden" id="ppcart_payment_method" name="ppcart_payment_method" value="<?php echo esc_attr($subscription_order->pay_method); ?>">
                    <div class="ppcart-subscription-wrap">
                        <?php if ($subscription_order->sub_payment) : ?>
                            <div class="ppcart-order-header">
                                <h3 class="ppcart-heading"><?php echo esc_html($subscription_order->product_name); ?></h3>
                            </div>
                            <div class="ppcart-order-premium">
                                <div class="ppcart-premium-info">
                                    <?php echo esc_html($subscription_order->sub_item_name); ?> - <?php echo wp_kses_post($subscription_order->sub_payment); ?>
                                </div>
                                <div class="ppcart-premium-addon">
                                    <?php if ($show_cancel) :  ?>
                                    <a id="ppcart-cancel-sub" title="<?php esc_attr_e('Cancel Subscription', 'publishpress-cart'); ?>" class="ppcart-cancel-sub" href="#" data-id="<?php echo esc_attr($subscription_post_id); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-' . $subscription_post_id . '-cancel')); ?>"><?php esc_html_e('Cancel', 'publishpress-cart'); ?></a>
                                    <?php endif; ?>
                                    <?php if ($show_pause) :  ?>
                                        <?php if ($subscription_order->status != 'paused') :?>
                                            <a id="ppcart-pause_sub" title="<?php esc_attr_e('Pause Subscription', 'publishpress-cart'); ?>" class="ppcart-pause-restart-sub" href="#" data-action="paused" data-id="<?php echo esc_attr($subscription_post_id); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-' . $subscription_post_id . '-pause')); ?>"><?php esc_html_e('Pause', 'publishpress-cart'); ?></a>
                                        <?php else :?>
                                            <a id="ppcart-pause_sub" title="<?php esc_attr_e('Restart Subscription', 'publishpress-cart'); ?>" class="ppcart-pause-restart-sub" href="#" data-action="started" data-id="<?php echo esc_attr($subscription_post_id); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-' . $subscription_post_id . '-resume')); ?>"><?php esc_html_e('Resume', 'publishpress-cart'); ?></a>
                                        <?php endif;?>
                                    <?php endif; ?>
                                    <?php do_action('ppcart_account_subscription_action_links', $subscription_order);?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array($subscription_order->status, $update_statuses)) :?>
                            <?php do_action('ppcart_show_' . $subscription_order->pay_method . '_payment_method', $subscription_order);?>
                        <?php endif;?>

                        <!-- Show Upgrade/downgrade plans -->
                        <?php do_action('ppcart_account_before_subscription_details', $subscription_order); ?>
                    </div>

                    <div class="ppcart-subscription-wrap">
                        <div class="ppcart-order-header">
                            <h4 class="ppcart-heading"><?php esc_html_e('Details', 'publishpress-cart'); ?></h4>
                        </div>
                        <table class="ppcart-subscription-table" cellpadding="6">
                            <?php if (isset($subscription_order->start_date) && $subscription_order->start_date) : ?>
                            <tr>
                                <td width="200"><?php esc_html_e('Start Date', 'publishpress-cart'); ?></td>
                                <td class="ppcart-payment-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription_order->start_date))); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (isset($subscription_order->sub_end_date) && $subscription_order->sub_end_date) : ?>
                                <tr>
                                    <td><?php esc_html_e('End Date ', 'publishpress-cart'); ?></td>
                                    <td class="ppcart-payment-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription_order->sub_end_date))); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><?php esc_html_e('Next Payment', 'publishpress-cart'); ?></td>
                                <td class="ppcart-payment-date">
                                <?php
                        if (!is_numeric($subscription_order->sub_next_bill_date)) {
                            $subscription_order->sub_next_bill_date = strtotime($subscription_order->sub_next_bill_date);
                        }
        $next = date_i18n(get_option('date_format'), $subscription_order->sub_next_bill_date);
        if ($subscription_order->status == 'paused' || $subscription_order->status == 'canceled' || $subscription_order->cancel_date) {
            echo '--';
        } else {
            echo esc_html($next);
        }
        ?>
                                </td>
                            </tr>
                            <?php if ($subscription_order->cancel_date && $subscription_order->status != 'canceled' && $next != '--') : ?>
                            <tr>
                                <td><?php esc_html_e('Cancels on', 'publishpress-cart'); ?></td>
                                <td><?php echo esc_html($next); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                        <?php do_action('ppcart_my_account_subscription_detail_after_details', $subscription_order, $sub); ?>
                    </div>
                </div>
    <?php } else {
        esc_html_e('No subscription found.', 'publishpress-cart');
    } ?>
<?php } else {
        esc_html_e('No subscription found.', 'publishpress-cart');
}?>
    </div>
    <!-- Update Payment Method -->
    <?php
            if (isset($subscription_order) && $subscription_order->pay_method == 'stripe') {
                ppcart_template('my-account/forms/change-card', '', [ 'plan' => $subscription_post_id ]);
            }
do_action('ppcart_subscription_detail_modals', $sub);
?>
    <!--/ Update Payment Method -->
</div>
