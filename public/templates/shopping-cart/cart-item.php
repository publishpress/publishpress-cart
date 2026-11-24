<?php

if (! defined('ABSPATH')) {
    exit;
}

$item = $attr['item'];
?>
<li class="ppcart-shopping-cart-item" data-ppcart-cart-key="<?php echo esc_attr($item['key']); ?>">
    <?php if ($item['image']) : ?>
        <img class="ppcart-shopping-cart-thumbnail" src="<?php echo esc_url($item['image']['url']); ?>" alt="<?php echo esc_attr($item['image']['alt']); ?>" width="60" height="60">
    <?php else : ?>
        <span class="ppcart-shopping-cart-thumbnail ppcart-shopping-cart-placeholder" aria-hidden="true"></span>
    
    <?php endif; ?>
    <div class="ppcart-shopping-cart-item-name">
        <a href="<?php echo esc_url($item['permalink']); ?>"><?php echo esc_html($item['product_name']); ?></a>
        <span><?php echo esc_html($item['plan_name']); ?></span>
        <?php if ($item['quantity'] > 1) : ?>
<span><?php echo esc_html(sprintf(__('Quantity: %d', 'publishpress-cart'), $item['quantity'])); ?></span>
        <?php endif; ?>
    </div>
    <div class="ppcart-shopping-cart-item-actions">
        <strong><?php echo wp_kses_post(ppcart_format_price($item['subtotal'])); ?></strong>
        <form method="post" action="<?php echo esc_url($attr['cart']['links']['cart']); ?>" class="ppcart-shopping-cart-remove">
            <input type="hidden" name="ppcart_remove_from_cart" value="<?php echo esc_attr($item['key']); ?>">
            <input type="hidden" name="ppcart_shopping_cart_nonce" value="<?php echo esc_attr(wp_create_nonce('ppcart_remove_from_cart')); ?>">
            <button type="submit" aria-label="<?php echo esc_attr(sprintf(__('Remove %s', 'publishpress-cart'), $item['product_name'])); ?>"><?php esc_html_e('Remove', 'publishpress-cart'); ?></button>
        </form>
    </div>
</li>
