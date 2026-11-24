<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-shopping-cart-summary">
    <h2 class="ppcart-shopping-cart-title"><?php esc_html_e('Order summary', 'publishpress-cart'); ?></h2>
    <?php foreach ($attr['cart']['totals']['rows'] as $row) : ?>
        <div class="ppcart-shopping-cart-total-row" data-ppcart-total="<?php echo esc_attr($row['type']); ?>">
            <span><?php echo esc_html($row['label']); ?></span><strong><?php echo wp_kses_post($row['html']); ?></strong>
        </div>
    <?php endforeach; ?>
    <?php do_action('ppcart_shopping_cart_after_totals', $attr['priced_cart']); ?>
    <a class="ppcart-shopping-cart-button" href="<?php echo esc_url($attr['cart']['links']['checkout']); ?>"><?php esc_html_e('Checkout', 'publishpress-cart'); ?></a>
</div>
