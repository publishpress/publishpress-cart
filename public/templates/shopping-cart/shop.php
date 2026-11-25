<?php

if (! defined('ABSPATH')) {
    exit;
}

$attributes = $attr['attributes'];
?>
<div class="ppcart-shopping-cart ppcart-shop" style="<?php echo esc_attr($attr['style']); ?>">
    <?php if ($attr['cards']) : ?>
        <ul class="ppcart-shop-grid">
            <?php foreach ($attr['cards'] as $card) : ?>
                <?php echo ppcart_get_template('shopping-cart/shop-card', '', ['card' => $card, 'attributes' => $attributes]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output. ?>
            <?php endforeach; ?>
        </ul>
        <?php echo ppcart_get_template('shopping-cart/shop-pagination', '', ['pages' => $attr['pagination']]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output. ?>
    <?php else : ?>
        <p><?php esc_html_e('No products found.', 'publishpress-cart'); ?></p>
    <?php endif; ?>
</div>
