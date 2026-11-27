<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! ppcart_is_order_post_type($post_type) || 'order' !== $section_slug) {
    return;
}

$cart_order = $this->get_current_edit_order();
if (! $cart_order) {
    return;
}

$stripe_fees         = PPCart_Stripe_Fees::get_order_fees((int) $cart_order->id);
$stripe_fees_missing = ! $stripe_fees && PPCart_Stripe_Fees::needs_fetch((int) $cart_order->id);
?>
<div class="ppcart-edit-order-summary">
    <div>
        <span><?php esc_html_e('Order date', 'publishpress-cart'); ?></span>
        <strong><?php echo esc_html(get_the_date('M j, Y g:i a', $cart_order->id)); ?></strong>
    </div>
    <div>
        <span><?php esc_html_e('Order amount', 'publishpress-cart'); ?></span>
        <strong><?php echo wp_kses_post(ppcart_format_price($cart_order->amount)); ?></strong>
    </div>
    <?php if ($stripe_fees) : ?>
        <div class="ppcart-edit-stripe-fee">
            <span><?php esc_html_e('Stripe fee', 'publishpress-cart'); ?></span>
            <strong><?php echo wp_kses_post(ppcart_format_price($stripe_fees['fee'])); ?></strong>
        </div>
        <?php if ($stripe_fees['platform_fee'] > 0) : ?>
            <div class="ppcart-edit-stripe-platform-fee">
                <span><?php esc_html_e('Platform fee', 'publishpress-cart'); ?></span>
                <strong><?php echo wp_kses_post(ppcart_format_price($stripe_fees['platform_fee'])); ?></strong>
            </div>
        <?php endif; ?>
        <div class="ppcart-edit-stripe-net">
            <span><?php esc_html_e('Net after fees', 'publishpress-cart'); ?></span>
            <strong><?php echo wp_kses_post(ppcart_format_price($stripe_fees['net'])); ?></strong>
        </div>
    <?php elseif ($stripe_fees_missing) : ?>
        <div class="ppcart-edit-stripe-fee">
            <span><?php esc_html_e('Stripe fee', 'publishpress-cart'); ?></span>
            <strong><span class="ppcart-empty"><?php esc_html_e('Not fetched yet', 'publishpress-cart'); ?></span></strong>
        </div>
    <?php endif; ?>
    <div class="ppcart-edit-ip">
        <span><?php esc_html_e('IP address', 'publishpress-cart'); ?></span>
        <strong><?php echo ! empty($cart_order->ip_address) ? esc_html($cart_order->ip_address) : '<span class="ppcart-empty">&mdash;</span>'; ?></strong>
    </div>
</div>
<?php
