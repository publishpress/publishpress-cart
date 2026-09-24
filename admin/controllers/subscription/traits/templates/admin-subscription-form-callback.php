<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen context check.
if (! ppcart_is_subscription_post_type($post->post_type) || !isset($_GET['post'])) {
    return;
}

$order_obj = new PPCart_Subscription($post->ID);
$subscription = (object) $order_obj->get_data();

$product_id = $subscription->product_id;
$subscription_gateway_id = ! empty($subscription->subscription_id) ? $subscription->subscription_id : ($subscription->stripe_subscription_id ?? '');
$is_stripe_subscription = 'stripe' === $subscription->pay_method && '' !== $subscription_gateway_id;
$has_subscription_thumbnail = ! empty($product_id) && has_post_thumbnail((int) $product_id);
$installments = $subscription->sub_installments;
if ($installments == '-1') {
    $installments = '&infin;';
}
?>
<div class="ppcart-product-info ppcart-product-table meta-box-sortables ui-sortable">
    <table cellpadding="0" cellspacing="0" class="ppcart-order-items ppcart-subscription-line-items" width="100%">
        <colgroup>
            <col class="ppcart-line-item-thumb-col<?php echo $has_subscription_thumbnail ? '' : ' ppcart-line-item-thumb-col--empty'; ?>">
            <col class="ppcart-subscription-item-col">
            <col class="ppcart-subscription-quantity-col">
            <col class="ppcart-subscription-payments-col">
            <col class="ppcart-subscription-recurring-col">
            <col class="ppcart-subscription-total-col">
        </colgroup>
        <thead>
            <tr>
                <th class="item" colspan="2"><?php esc_html_e('Item', 'publishpress-cart'); ?></th>
                <th class="line_cost"><?php esc_html_e('Qty', 'publishpress-cart'); ?></th>
                <th class="line_cost"><?php esc_html_e('# of Payments', 'publishpress-cart'); ?></th>
                <th class="line_cost"><?php esc_html_e('Recurring Payment', 'publishpress-cart'); ?></th>
                <th class="line_cost"><?php esc_html_e('Total', 'publishpress-cart'); ?></th>
            </tr>
        </thead>
        <tbody id="order_line_items">

            <?php
            if (isset($subscription->main_product_sub) && $subscription->main_product_sub) { // Deprecated
                if ($subscription->main_product_sub) {
                    $sub = $subscription->main_product_sub;
                    $this->main_product_sub_row($subscription, $sub);
                }
            } elseif ($subscription->sub_payment) {
                $this->main_product_sub_row($subscription);
            } ?>

            <?php if (isset($subscription->tax_data) && is_object($subscription->tax_data) && $subscription->tax_desc) :
                $redeem_tax = false;
                if (isset($subscription->tax_data->redeem_vat) && $subscription->tax_data->redeem_vat) :
                    $redeem_tax = true;
                    if ($subscription->tax_data->type != 'inclusive') :
                        $subscription->tax_amount = 0;
                        $subscription->tax_rate = "0";
                    endif;
                endif; ?>
                <tr class="item">
                    <td style="padding:0;"></td>
                    <td class="name" colspan="4">
                        <?php echo esc_html($subscription->tax_desc . ' (' . $subscription->tax_rate . '%)'); ?>
                    </td>
                    <td class="item_cost">
                        <div class="view">
                            <?php ppcart_formatted_price($subscription->tax_amount); ?>
                        </div>
                    </td>
                </tr>
                <?php if ($redeem_tax && $subscription->tax_data->type == 'inclusive') : ?>
                    <tr class="item">
                        <td style="padding:0;"></td>
                        <td class="name" colspan="4">
                            <?php echo esc_html(get_option('_ppcart_vat_reverse_charge', "VAT Reversal")); ?>
                        </td>
                        <td class="item_cost">
                            <div class="view">
                                - <?php ppcart_formatted_price($subscription->tax_amount); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>

            <tr class="item items-total" style="font-weight: bold">
                <td style="padding:0;"></td>
                <td class="name" colspan="4">
                    <?php esc_html_e('Total', 'publishpress-cart') ?>
                </td>
                <td class="item_cost">
                    <div class="view">
                        <span class="ppcart-Price-amount amount">
                            <?php ppcart_formatted_price($subscription->sub_amount); ?>
                        </span>
                    </div>
                </td>
            </tr>
            <tr class="ppcart-subscription-action-row">
                <td colspan="6">
                    <div class="ppcart-subscription-actions">
                        <?php if ($subscription->status != 'canceled' && !$subscription->cancel_date) : ?>
                            <button type="button" class="button ppcart-subscription-action ppcart-subscription-action--danger ppcart_unsubscribe_btn"><?php esc_html_e('Cancel Subscription', 'publishpress-cart'); ?></button>
                            <?php if ($subscription->status != 'paused') : ?>
                                <button type="button" class="button ppcart-subscription-action ppcart_pause_restart" data-action="paused" data-id="<?php echo esc_attr($subscription->id); ?>"><?php esc_html_e('Pause', 'publishpress-cart'); ?></button>
                            <?php else : ?>
                                <button type="button" class="button ppcart-subscription-action ppcart_pause_restart" data-action="started" data-id="<?php echo esc_attr($subscription->id); ?>"><?php esc_html_e('Resume', 'publishpress-cart'); ?></button>
                            <?php endif; ?>
                        <?php else :
                            $cancel_text = __('Canceled', 'publishpress-cart');
                            if ($subscription->cancel_date && $subscription->status != 'canceled') :
                                $cancel_text = __('Cancellation Pending', 'publishpress-cart');
                            endif; ?>
                            <button type="button" class="button" disabled><?php echo esc_html($cancel_text); ?></button>
                        <?php endif; ?>
                        <?php if ($is_stripe_subscription) : ?>
                            <button
                                type="button"
                                class="button ppcart-subscription-action ppcart_sync_subscription"
                                data-id="<?php echo esc_attr($subscription_gateway_id); ?>"
                                data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-sync')); ?>"
                                title="<?php echo esc_attr__('Refresh status and next bill date from Stripe, and import any invoices missing from the order history', 'publishpress-cart'); ?>"
                            ><?php esc_html_e('Sync with Stripe', 'publishpress-cart'); ?></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php if ($is_stripe_subscription) : ?>
        <div
            id="ppcart-subscription-cancel-modal"
            class="ppcart-order-modal ppcart-subscription-cancel-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppcart-subscription-cancel-modal-title"
            hidden>
            <div class="ppcart-order-modal__backdrop" data-ppcart-subscription-cancel-close></div>
            <div class="ppcart-order-modal__dialog">
                <header class="ppcart-order-modal__header">
                    <div>
                        <div class="ppcart-order-modal__eyebrow"><?php esc_html_e('Stripe subscription', 'publishpress-cart'); ?></div>
                        <h3 id="ppcart-subscription-cancel-modal-title"><?php esc_html_e('Cancel subscription', 'publishpress-cart'); ?></h3>
                    </div>
                    <button type="button" class="ppcart-order-modal__close" aria-label="<?php esc_attr_e('Close', 'publishpress-cart'); ?>" data-ppcart-subscription-cancel-close>&times;</button>
                </header>
                <div class="ppcart-order-modal__body">
                    <fieldset class="ppcart-subscription-cancel-group">
                        <legend class="screen-reader-text"><?php esc_html_e('Cancel', 'publishpress-cart'); ?></legend>
                        <span class="ppcart-subscription-cancel-group__title" aria-hidden="true"><?php esc_html_e('Cancel', 'publishpress-cart'); ?></span>
                        <div class="ppcart-subscription-cancel-options">
                            <label>
                                <input type="radio" name="ppcart_subscription_cancel_timing" value="immediate" checked>
                                <span><?php esc_html_e('Immediately', 'publishpress-cart'); ?></span>
                            </label>
                            <label>
                                <input type="radio" name="ppcart_subscription_cancel_timing" value="period_end">
                                <span><?php esc_html_e('End of the current billing period', 'publishpress-cart'); ?></span>
                            </label>
                        </div>
                    </fieldset>
                    <fieldset class="ppcart-subscription-cancel-group">
                        <legend class="screen-reader-text"><?php esc_html_e('Refund', 'publishpress-cart'); ?></legend>
                        <span class="ppcart-subscription-cancel-group__title" aria-hidden="true"><?php esc_html_e('Refund', 'publishpress-cart'); ?></span>
                        <div class="ppcart-subscription-cancel-options">
                            <label>
                                <input type="radio" name="ppcart_subscription_refund_action" value="no_refund" checked>
                                <span><?php esc_html_e('No refund', 'publishpress-cart'); ?></span>
                            </label>
                            <label>
                                <input type="radio" name="ppcart_subscription_refund_action" value="refund">
                                <span><?php esc_html_e('Refund latest paid invoice', 'publishpress-cart'); ?></span>
                            </label>
                            <p class="ppcart-order-modal__description"><?php esc_html_e('Stripe records this refund as a credit note on the latest paid invoice.', 'publishpress-cart'); ?></p>
                        </div>
                    </fieldset>
                </div>
                <footer class="ppcart-order-modal__footer">
                    <button type="button" class="button" data-ppcart-subscription-cancel-close><?php esc_html_e("Don't cancel", 'publishpress-cart'); ?></button>
                    <button type="button" class="button button-primary ppcart-subscription-cancel-submit"><?php esc_html_e('Cancel subscription', 'publishpress-cart'); ?></button>
                </footer>
            </div>
        </div>
    <?php endif; ?>
</div>
<div class="ppcart-product-info ppcart-product-table meta-box-sortables ui-sortable">
    <div class="postbox">
        <h2><?php esc_html_e('Related Orders', 'publishpress-cart'); ?></h2>
    </div>

    <table cellpadding="0" cellspacing="0" class="ppcart-order-items" width="100%">
        <thead>
            <tr>
                <th class="item"><?php esc_html_e('Order Number', 'publishpress-cart'); ?></th>
                <th class="item"><?php esc_html_e('Relationship', 'publishpress-cart'); ?></th>
                <th class="item"><?php esc_html_e('Date', 'publishpress-cart'); ?></th>
                <th class="item"><?php esc_html_e('Status', 'publishpress-cart'); ?></th>
                <th class="line_cost"><?php esc_html_e('Total', 'publishpress-cart'); ?></th>
            </tr>
        </thead>
        <tbody id="order_line_items">

            <?php
            // The Query
            $args = [
                'post_type' => ppcart_query_post_types('order'),
                'orderby' => 'date',
                'order'   => 'ASC',
                'post_status' => ['any'],
                // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
                'posts_per_page' => -1,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to load orders linked to this subscription.
                'meta_query' => [
                    [
                        'key' => ppcart_meta_key('subscription_id'),
                        'value' => $post->ID,
                    ],
                ],
            ];
$the_query = new WP_Query($args);
$initial_order = true;

// The Loop
if ($the_query->have_posts()) {
    while ($the_query->have_posts()) {
        $the_query->the_post();
        $related_order = new PPCart_Order(get_the_ID()); ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="ppcart-order-item-name">#<?php echo esc_html(get_the_ID()); ?></a>
                        </td>
                        <td><?php echo esc_html($initial_order ? __('Initial Order', 'publishpress-cart') : __('Renewal Order', 'publishpress-cart')); ?> </td>
                        <td><?php echo esc_html(get_the_date('F d, Y h:i a', get_the_ID())); ?></td>
                        <td class="name"><?php echo esc_html($related_order->get_status()); ?></td>
                        <td class="sub_cost">
                            <div class="view">
                                <span class="ppcart-Price-amount amount">
                                    <?php ppcart_formatted_price(ppcart_get_post_meta(get_the_ID(), 'amount', true)); ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php
        $initial_order = false;
    }
}
/* Restore original Post Data */
wp_reset_postdata();

if (isset($subscription->ob_parent) || isset($subscription->us_parent) || isset($subscription->ds_parent)) {
    if (isset($subscription->ob_parent)) {
        $parent_id = $subscription->ob_parent;
    } elseif (isset($subscription->ds_parent)) {
        $parent_id = $subscription->ds_parent;
    } else {
        $parent_id = $subscription->us_parent;
    }
    $parent_prod_id = ppcart_get_post_meta($parent_id, 'product_id', true);
    $show_related = true;
}
if (isset($parent_id) && $parent_id) : ?>
                <?php $parent = new PPCart_Order($parent_id); ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(ppcart_get_edit_post_url($parent_id)); ?>" class="ppcart-order-item-name">#<?php echo esc_html($parent_id); ?></a>
                    </td>
                    <td><?php esc_html_e('Parent Order', 'publishpress-cart'); ?></td>
                    <td><?php echo esc_html(get_the_date('F d, Y h:i a', $parent_id)); ?></td>
                    <td class="name"><?php echo esc_html($parent->get_status()); ?></td>
                    <td class="sub_cost">
                        <div class="view">
                            <span class="ppcart-Price-amount amount">
                                <?php ppcart_formatted_price(ppcart_get_post_meta($parent_id, 'amount', true)); ?>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php
endif;

do_action('ppcart_subscription_related_orders', $order_obj);

?>

        </tbody>
    </table>
</div>
<?php
