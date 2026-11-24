<?php

if (! defined('ABSPATH')) {
    exit;
}

$card = $attr['card'];
$attributes = $attr['attributes'];
?>
<li class="ppcart-shop-card" data-ppcart-product="<?php echo esc_attr($card['product_id']); ?>">
    <?php if ($attributes['showImage'] && $card['image_id']) : ?>
        <a class="ppcart-shop-card-image" href="<?php echo esc_url($card['permalink']); ?>">
            <?php echo wp_get_attachment_image($card['image_id'], 'medium_large'); ?>
        </a>
    <?php endif; ?>
    <div class="ppcart-shop-card-body">
        <?php if ($card['purchased']) : ?>
            <span class="ppcart-shop-purchased"><?php esc_html_e('Purchased', 'publishpress-cart'); ?></span>
        <?php endif; ?>
        <h3><a href="<?php echo esc_url($card['permalink']); ?>"><?php echo esc_html($card['title']); ?></a></h3>
        <?php if ($attributes['showExcerpt'] && $card['excerpt']) : ?>
            <p class="ppcart-shop-excerpt"><?php echo esc_html($card['excerpt']); ?></p>
        <?php endif; ?>
        <?php if ($attributes['showPrice']) : ?>
            <div class="ppcart-shop-price"><?php echo esc_html($card['price_label']); ?></div>
        <?php endif; ?>
        <?php if ('add' === $card['action']) : ?>
            <form method="post" class="ppcart-shopping-cart-add">
                <input type="hidden" name="ppcart_add_to_cart" value="<?php echo esc_attr($card['form']['product_id']); ?>">
                <input type="hidden" name="ppcart_shopping_cart_nonce" value="<?php echo esc_attr($card['form']['nonce']); ?>">
                <?php if (count($card['plans']) > 1) : ?>
                    <label for="ppcart-shop-plan-<?php echo esc_attr($card['product_id']); ?>"><?php esc_html_e('Payment plan', 'publishpress-cart'); ?></label>
                    <select id="ppcart-shop-plan-<?php echo esc_attr($card['product_id']); ?>" name="plan_id">
                        <?php foreach ($card['plans'] as $plan) : ?>
                            <option data-ppcart-in-cart="<?php echo $plan['in_cart'] ? 'true' : 'false'; ?>" value="<?php echo esc_attr($plan['plan_id']); ?>" <?php selected($card['selected'], $plan['plan_id']); ?>><?php echo esc_html($plan['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <input type="hidden" name="plan_id" value="<?php echo esc_attr($card['selected']); ?>">
                <?php endif; ?>
                <button class="ppcart-shopping-cart-button<?php echo $card['plans'][0]['in_cart'] ? ' ppcart-shopping-cart-in-cart' : ''; ?>" type="submit" data-ppcart-in-cart="<?php echo $card['plans'][0]['in_cart'] ? 'true' : 'false'; ?>"><?php echo $card['plans'][0]['in_cart'] ? esc_html__('✓ In cart', 'publishpress-cart') : esc_html__('Add to cart', 'publishpress-cart'); ?></button>
            </form>
        <?php else : ?>
            <a class="ppcart-shopping-cart-button ppcart-shopping-cart-button-secondary" href="<?php echo esc_url($card['permalink']); ?>"><?php echo 'subscribe' === $card['action'] ? esc_html__('Subscribe', 'publishpress-cart') : esc_html__('View', 'publishpress-cart'); ?></a>
        <?php endif; ?>
    </div>
</li>
