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
    <div class="ppcart-edit-ip">
        <span><?php esc_html_e('IP address', 'publishpress-cart'); ?></span>
        <strong><?php echo ! empty($cart_order->ip_address) ? esc_html($cart_order->ip_address) : '<span class="ppcart-empty">&mdash;</span>'; ?></strong>
    </div>
</div>
<?php
