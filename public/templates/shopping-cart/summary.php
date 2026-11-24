<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_checkout = ! empty($attr['checkout']);
?>
<div
    class="ppcart-shopping-cart-summary"
    <?php if ($ppcart_checkout) : ?>
        data-ppcart-checkout-summary
        data-ppcart-revision="<?php echo esc_attr($attr['cart']['revision']); ?>"
        data-ppcart-amount="<?php echo esc_attr(PPCart_Shopping_Cart_Stripe::amount((float) $attr['cart']['totals']['total'])); ?>"
    <?php endif; ?>
>
    <?php if ($ppcart_checkout) : ?>
        <div class="ppcart-shopping-cart-summary-header">
            <h2 class="ppcart-shopping-cart-title"><?php esc_html_e('Order summary', 'publishpress-cart'); ?></h2>
            <?php if ($attr['cart']['links']['cart']) : ?>
                <a class="ppcart-shopping-cart-link" href="<?php echo esc_url($attr['cart']['links']['cart']); ?>"><?php esc_html_e('Edit cart', 'publishpress-cart'); ?></a>
            <?php endif; ?>
        </div>
        <?php echo ppcart_get_template('shopping-cart/notices', '', $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
        <?php if (! $attr['cart']['count']) : ?>
            <p class="ppcart-shopping-cart-empty-note"><?php esc_html_e('The items in the cart show here.', 'publishpress-cart'); ?></p>
        <?php endif; ?>
        <ul class="ppcart-shopping-cart-items ppcart-shopping-cart-summary-items">
            <?php foreach ($attr['cart']['items'] as $ppcart_item) : ?>
                <li class="ppcart-shopping-cart-item">
                    <?php if ($ppcart_item['image']) : ?>
                        <img class="ppcart-shopping-cart-thumbnail" src="<?php echo esc_url($ppcart_item['image']['url']); ?>" alt="" width="44" height="44">
                    <?php else : ?>
                        <span class="ppcart-shopping-cart-thumbnail ppcart-shopping-cart-placeholder" aria-hidden="true"></span>
                    <?php endif; ?>
                    <div class="ppcart-shopping-cart-item-name">
                        <b><?php echo esc_html($ppcart_item['product_name']); ?></b>
                        <span><?php echo esc_html($ppcart_item['plan_name']); ?></span>
                        <?php if ($ppcart_item['quantity'] > 1) : ?>
                            <?php /* translators: %d: quantity of one cart item. */ ?>
                            <span><?php echo esc_html(sprintf(__('Quantity: %d', 'publishpress-cart'), $ppcart_item['quantity'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <strong><?php echo wp_kses_post(ppcart_format_price($ppcart_item['subtotal'])); ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <h2 class="ppcart-shopping-cart-title"><?php esc_html_e('Order summary', 'publishpress-cart'); ?></h2>
    <?php endif; ?>
    <?php foreach ($attr['cart']['totals']['rows'] as $row) : ?>
        <div class="ppcart-shopping-cart-total-row" data-ppcart-total="<?php echo esc_attr($row['type']); ?>">
            <span><?php echo esc_html($row['label']); ?></span><strong><?php echo wp_kses_post($row['html']); ?></strong>
        </div>
    <?php endforeach; ?>
    <?php do_action('ppcart_shopping_cart_after_totals', $attr['priced_cart']); ?>
    <?php if (! $ppcart_checkout) : ?>
        <a class="ppcart-shopping-cart-button" href="<?php echo esc_url($attr['cart']['links']['checkout']); ?>"><?php esc_html_e('Checkout', 'publishpress-cart'); ?></a>
    <?php endif; ?>
</div>
