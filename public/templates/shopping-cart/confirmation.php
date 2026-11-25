<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<div
    class="ppcart-shopping-cart ppcart-shopping-cart-confirmation"
    data-ppcart-cart-confirmation
    <?php if (! $attr['placed']) : ?>
        data-ppcart-pending-order="<?php echo esc_attr($attr['order_id']); ?>"
        data-ppcart-pending-token="<?php echo esc_attr($attr['token']); ?>"
    <?php endif; ?>
    style="<?php echo esc_attr($attr['style']); ?>"
>
    <?php if ($attr['placed']) : ?>
        <div class="ppcart-shopping-cart-confirmation-message" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-order-confirmation-message')); ?>">
            <?php echo wp_kses_post(wpautop($attr['message'])); ?>
        </div>
    <?php else : ?>
        <p class="ppcart-shopping-cart-notice"><?php esc_html_e('We are waiting for your payment. This page shows your order when the payment is complete.', 'publishpress-cart'); ?></p>
    <?php endif; ?>
    <?php ppcart_template('shortcodes/receipt', '', $attr['items']); ?>
</div>
