<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
     * Render the legacy order details shortcode output.
     *
     * @param int $order_id Order ID.
     * @return void
     */
function ppcart_order_details($order_id)
{
    $order = apply_filters('ppcart_order', new PPCart_Order($order_id));
    $order = (object) $order->get_data();
    if (isset($order->main_offer)) { // backwards compatibility
        $order->main_offer_amt = $order->main_offer["plan"]->initial_payment;
    }
    $total = 0;
    ?>
    <div id="ppcart-order-details">
        <h3><?php esc_html_e("Order Details", "publishpress-cart"); ?></h3>
        <div class="ppcart-order-table">
            <div class="item ppcart-heading"><strong><?php esc_html_e("Product", "publishpress-cart"); ?></strong></div>
            <div class="order-total ppcart-heading"><strong><?php esc_html_e("Price", "publishpress-cart"); ?></strong></div>

            <?php
            if ($order->subscription_id) {
                $sub = new PPCart_Subscription($order->subscription_id);
                $subarr = $sub->get_data();
                $text = $subarr['sub_payment_terms'];

                if (isset($sub->free_trial_days) && $sub->free_trial_days > 0) {
                    /* translators: %s: number of trial days. */
                    $text .= ', ' . sprintf(__('%s-day trial', 'publishpress-cart'), $sub->free_trial_days);
                }

                if (isset($sub->sign_up_fee) && $sub->sign_up_fee > 0) {
                    /* translators: %s: sign-up fee amount. */
                    $text .= ', ' . sprintf(__('%s sign-up fee', 'publishpress-cart'), ppcart_format_price($sub->sign_up_fee));
                }

                if (isset($sub->sub_discount_duration)) {
                    $text .= '<br><strong>' . __('Coupon:', 'publishpress-cart') . ' </strong> ';
                    /* translators: 1: discount amount, 2: number of months. */
                    $text .= sprintf(__('%1$s off for %2$d months', 'publishpress-cart'), ppcart_format_price($sub->sub_discount), $sub->sub_discount_duration);
                    $text = apply_filters('ppcart_format_subscription_order_detail', $text, $subarr['sub_payment_terms'], $sub->free_trial_days, $order->plan, $sub->sign_up_fee, $sub->sub_discount, $sub->sub_discount_duration);
                }
            }
    ?>

            <div class="item">
                <?php echo '<strong>' . esc_html($order->product_name) . '</strong>'; ?>
                <?php if ($order->plan && $order->plan->type == 'recurring') {
                    echo '<br><small>' . wp_kses_post($text) . '</small>';
                } ?>
                <?php if ($order->purchase_note) {
                    echo '<br><span class="ppcart-purchase-note">' . wp_kses_post($order->purchase_note) . '</span>';
                } ?>
            </div>

            <div class="order-total">
                <?php
                if ($order->main_offer_amt == 0 && !$order->subscription_id) {
                    esc_html_e("Free", "publishpress-cart");
                } else {
                    ppcart_formatted_price($order->main_offer_amt);
                }
    $total += floatval($order->main_offer_amt);

    ?>
            </div>



            <?php if (isset($order->custom_prices)) :
                foreach ($order->custom_prices as $price) : ?>
                <div class="item"><strong><?php echo esc_html($price['label'] . ' x ' . $price['qty']); ?></strong></div>
                <div class="order-total">
                    <?php ppcart_formatted_price($price['price']); ?>
                </div>
                <?php $total += floatval($price['price']); ?>
                <?php endforeach;
            endif; ?>

            <?php if (!empty($order->order_bumps) && is_array($order->order_bumps)) : ?>
                <?php foreach ($order->order_bumps as $order_bump) :?>
                    <div class="item">
                        <?php echo '<strong>' . esc_html($order_bump['name']) . '</strong>'; ?>
                        <?php if ($order->plan->type != 'recurring' && $order->subscription_id) {
                            echo '<br><small>' . esc_html($text) . '</small>';
                        } ?>
                        <?php if ($order_bump['purchase_note']) {
                            echo '<br><span class="ppcart-purchase-note">' . wp_kses_post($order_bump['purchase_note']) . '</span>';
                        } ?>
                    </div>
                    <div class="order-total">
                        <?php ppcart_formatted_price($order_bump['amount']); ?>
                    </div>
                    <?php $total += floatval($order_bump['amount']); ?>
                <?php endforeach; ?>
            <?php endif; ?>


            <?php if (isset($order->order_child)) : ?>
                <?php foreach ($order->order_child as $child_order) :
                    $productAmount = floatval($child_order['amount']);
                    if (isset($child_order['tax_amount']) && !empty($child_order['tax_amount'])) {
                        $productAmount -= floatval($child_order['tax_amount']);
                    }
                    ?>
                    <div class="item">
                        <?php echo '<strong>' . esc_html($child_order['product_name']) . '</strong>';
                    if ($child_order['subscription_id']) {
                        $sub = new PPCart_Subscription($child_order['subscription_id']);
                        $sub = $sub->get_data();
                        echo '<br><small>' . wp_kses_post($sub['sub_payment_terms']) . '</small>';
                    }
                    ?>
                        <?php if ($child_order['purchase_note']) {
                            echo '<br><span class="ppcart-purchase-note">' . wp_kses_post($child_order['purchase_note']) . '</span>';
                        } ?>
                    </div>
                    <div class="order-total">
                        <?php ppcart_formatted_price($productAmount); ?>
                    </div>
                    <?php $total += floatval($productAmount);

                    if (isset($child_order['tax_data']) && !empty($child_order['tax_amount'])) :
                        if (!$order->tax_amount) {
                            $order->tax_amount = 0;
                        }
                        $order->tax_amount += $child_order['tax_amount'];
                    endif;
                endforeach; ?>
            <?php endif; ?>

            <?php if (is_object($order->tax_data) || ($order->coupon_id && in_array($order->coupon['type'], ['cart-percent', 'cart-fixed']))) :?>
                <div class="item" style="border:0;"><strong><?php esc_html_e("Subtotal", "publishpress-cart"); ?></strong></div>
                <div class="order-total" style="border:0;"><strong><?php ppcart_formatted_price($total); ?></strong></div>
                <br><br>

                <?php if ($order->coupon_id && in_array($order->coupon['type'], ['cart-percent', 'cart-fixed'])) : ?>
                    <div class="item"><?php esc_html_e("Coupon: ", "publishpress-cart");
                    echo esc_html($order->coupon_id); ?></div>
                    <div class="order-total">- <?php ppcart_formatted_price($order->coupon['discount_amount']); ?></div>
                    <?php $total -= floatval($order->coupon['discount_amount']); ?>
                <?php endif; ?>

                <?php if (is_object($order->tax_data)) :
                    $redeem_tax = false;
                    if (isset($order->tax_data->redeem_vat) && $order->tax_data->redeem_vat) :
                        $redeem_tax = true;
                        if ($order->tax_data->type != 'inclusive') :
                            $order->tax_amount = 0;
                            $order->tax_rate = "0%";
                        endif;
                    endif;?>
                    <div class="item"><?php echo esc_html($order->tax_desc . ' (' . $order->tax_rate . ')'); ?></div>
                    <div class="order-total">
                        <?php ppcart_formatted_price($order->tax_amount); ?>
                    </div>
                    <?php if (is_countable($order->tax_data) && $order->tax_data->type == 'inclusive' && $redeem_tax) : ?>
                        <div class="item"><?php echo esc_html(get_option('_ppcart_vat_reverse_charge', "VAT Reversal")); ?></div>
                        <div class="order-total">
                            -<?php ppcart_formatted_price($order->tax_amount); ?>
                        </div>
                    <?php endif;?>
                    <?php if (!isset($order->tax_data->type) || $order->tax_data->type != 'inclusive' && !$redeem_tax) {
                        $total += floatval($order->tax_amount);
                    } ?>
                    <?php if (!isset($order->tax_data->type) || $order->tax_data->type == 'inclusive' && $redeem_tax) {
                        $total -= floatval($order->tax_amount);
                    } ?>
                <?php endif; ?>

            <?php endif; ?>

            <?php if ($total) : ?>
            <div class="item" style="border: 0"><strong><?php esc_html_e("Order Total", "publishpress-cart"); ?></strong></div>
            <div class="order-total" style="border: 0">
                <strong><?php ppcart_formatted_price($total); ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
