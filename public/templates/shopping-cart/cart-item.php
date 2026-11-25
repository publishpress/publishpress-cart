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
            <span class="ppcart-shopping-cart-unit-price">
                <?php
                /* translators: %s: price of one item. */
                echo wp_kses_post(sprintf(__('%s each', 'publishpress-cart'), $item['price_html']));
                ?>
            </span>
        <?php endif; ?>
        <?php if ($item['max_quantity'] > 1) : ?>
            <form method="post" action="<?php echo esc_url($attr['cart']['links']['cart']); ?>" class="ppcart-shopping-cart-quantity">
                <input type="hidden" name="ppcart_cart_quantity_key" value="<?php echo esc_attr($item['key']); ?>">
                <input type="hidden" name="ppcart_shopping_cart_nonce" value="<?php echo esc_attr(wp_create_nonce('ppcart_update_cart_quantity')); ?>">
                <button type="submit" name="ppcart_cart_quantity" value="<?php echo esc_attr($item['quantity'] - 1); ?>" data-ppcart-quantity-step="-1" aria-label="<?php echo esc_attr(sprintf(__('Decrease the quantity of %s', 'publishpress-cart'), $item['product_name'])); ?>" <?php disabled($item['quantity'] <= 1); ?>>&minus;</button>
                <output aria-label="<?php echo esc_attr(sprintf(__('Quantity of %s', 'publishpress-cart'), $item['product_name'])); ?>"><?php echo esc_html($item['quantity']); ?></output>
                <button type="submit" name="ppcart_cart_quantity" value="<?php echo esc_attr($item['quantity'] + 1); ?>" data-ppcart-quantity-step="1" aria-label="<?php echo esc_attr(sprintf(__('Increase the quantity of %s', 'publishpress-cart'), $item['product_name'])); ?>" <?php disabled($item['quantity'] >= $item['max_quantity']); ?>>+</button>
            </form>
        <?php elseif ($item['quantity'] > 1) : ?>
            <span>
                <?php
                /* translators: %d: quantity of the item. */
                echo esc_html(sprintf(__('Quantity: %d', 'publishpress-cart'), $item['quantity']));
                ?>
            </span>
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
