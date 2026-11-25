<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-shopping-cart ppcart-mini-cart" style="<?php echo esc_attr($attr['style']); ?>">
    <a class="ppcart-mini-cart-trigger" href="<?php echo esc_url($attr['links']['cart']); ?>"
        <?php if ($attr['attributes']['opensdrawer']) : ?>
            data-ppcart-open-drawer aria-haspopup="dialog" aria-expanded="false"
        <?php endif; ?>
    >
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 3h3l2.6 12.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg>
        <?php if ($attr['attributes']['showcount']) : ?>
            <span class="ppcart-mini-cart-count" data-ppcart-cart-count>0</span>
        <?php endif; ?>
        <span><?php esc_html_e('Cart', 'publishpress-cart'); ?></span>
        <?php if ($attr['attributes']['showtotal']) : ?>
            <span data-ppcart-cart-total></span>
        <?php endif; ?>
    </a>
</div>
