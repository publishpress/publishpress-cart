<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_continue = $attr['continue_text'] ?? ppcart_shopping_cart_continue_text();
?>
<div class="ppcart-shopping-cart ppcart-shopping-cart-cart" data-ppcart-cart data-ppcart-continue-text="<?php echo esc_attr($ppcart_continue); ?>" style="<?php echo esc_attr($attr['style']); ?>">
    <?php echo ppcart_get_template('shopping-cart/notices', '', $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
    <?php if ($attr['cart']['count']) : ?>
        <div class="ppcart-shopping-cart-columns">
            <div class="ppcart-shopping-cart-main">
                <ul class="ppcart-shopping-cart-items ppcart-shopping-cart-lines" aria-label="<?php esc_attr_e('Your cart', 'publishpress-cart'); ?>">
                    <?php foreach ($attr['cart']['items'] as $item) : ?>
                        <?php echo ppcart_get_template('shopping-cart/cart-item', '', ['item' => $item] + $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
                    <?php endforeach; ?>
                </ul>
                <?php if ('' !== $ppcart_continue) : ?>
                    <a class="ppcart-shopping-cart-link" href="<?php echo esc_url($attr['cart']['links']['shop']); ?>" data-ppcart-continue><?php echo esc_html($ppcart_continue); ?></a>
                <?php endif; ?>
            </div>
            <?php echo ppcart_get_template('shopping-cart/summary', '', $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
        </div>
    <?php else : ?>
        <div class="ppcart-shopping-cart-empty">
            <div class="ppcart-shopping-cart-empty-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h3l2.6 12.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg>
            </div>
            <h2><?php esc_html_e('Your cart is empty.', 'publishpress-cart'); ?></h2>
            <p><?php esc_html_e('Add a product from the shop to start.', 'publishpress-cart'); ?></p>
            <a class="ppcart-shopping-cart-button" href="<?php echo esc_url($attr['cart']['links']['shop']); ?>"><?php esc_html_e('Browse the shop', 'publishpress-cart'); ?></a>
        </div>
    <?php endif; ?>
</div>
