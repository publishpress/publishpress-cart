<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<section class="ppcart-shopping-cart-drawer" data-ppcart-drawer role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Your cart', 'publishpress-cart'); ?>" tabindex="-1">
    <header class="ppcart-shopping-cart-drawer-header">
        <h2 class="ppcart-shopping-cart-title"><?php esc_html_e('Your cart', 'publishpress-cart'); ?> (<span data-ppcart-cart-count><?php echo esc_html($attr['cart']['count']); ?></span>)</h2>
        <button class="ppcart-shopping-cart-close" type="button" data-ppcart-close-drawer aria-label="<?php esc_attr_e('Close cart', 'publishpress-cart'); ?>">×</button>
    </header>
    <p class="ppcart-shopping-cart-result" data-ppcart-shopping-cart-result aria-hidden="true" hidden></p>
    <div class="ppcart-shopping-cart-drawer-body">
        <?php echo ppcart_get_template('shopping-cart/notices', '', $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
        <?php if (! empty($attr['loading'])) : ?>
            <div class="ppcart-shopping-cart-loading" aria-hidden="true">
                <?php for ($ppcart_row = 0; $ppcart_row < 3; $ppcart_row++) : ?>
                    <div class="ppcart-shopping-cart-loading-row"><span></span><span></span><span></span></div>
                <?php endfor; ?>
            </div>
            <p class="ppcart-shopping-cart-sr"><?php esc_html_e('Loading your cart.', 'publishpress-cart'); ?></p>
        <?php elseif ($attr['cart']['count']) : ?>
            <ul class="ppcart-shopping-cart-items">
                <?php foreach ($attr['cart']['items'] as $item) : ?>
                    <?php echo ppcart_get_template('shopping-cart/cart-item', '', ['item' => $item] + $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <div class="ppcart-shopping-cart-empty">
                <h3><?php esc_html_e('Your cart is empty.', 'publishpress-cart'); ?></h3>
                <p><?php esc_html_e('Add a product from the shop to start.', 'publishpress-cart'); ?></p>
                <a class="ppcart-shopping-cart-button" href="<?php echo esc_url($attr['cart']['links']['shop']); ?>"><?php esc_html_e('Browse the shop', 'publishpress-cart'); ?></a>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($attr['cart']['count']) : ?>
        <footer class="ppcart-shopping-cart-drawer-footer">
            <?php echo ppcart_get_template('shopping-cart/summary', '', $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
            <a class="ppcart-shopping-cart-link" href="<?php echo esc_url($attr['cart']['links']['cart']); ?>"><?php esc_html_e('View cart', 'publishpress-cart'); ?></a>
        </footer>
    <?php endif; ?>
</section>
