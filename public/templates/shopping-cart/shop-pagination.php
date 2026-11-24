<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_pages = $attr['pages'];
if ('none' === $ppcart_pages['style']) {
    return;
}
?>
<?php if ('load_more' === $ppcart_pages['style']) : ?>
    <?php if ($ppcart_pages['next']) : ?>
        <div class="ppcart-shop-load-more">
            <a class="ppcart-shopping-cart-button ppcart-shopping-cart-button-secondary" href="<?php echo esc_url($ppcart_pages['next']); ?>" data-ppcart-load-more><?php esc_html_e('Load more products', 'publishpress-cart'); ?></a>
        </div>
    <?php endif; ?>
<?php else : ?>
    <nav class="ppcart-shop-pagination" aria-label="<?php esc_attr_e('Product pages', 'publishpress-cart'); ?>">
        <?php if ($ppcart_pages['previous']) : ?>
            <a class="page-numbers prev" href="<?php echo esc_url($ppcart_pages['previous']); ?>" rel="prev"><span aria-hidden="true">←</span> <?php esc_html_e('Previous', 'publishpress-cart'); ?></a>
        <?php endif; ?>
        <?php if ('numbers' === $ppcart_pages['style']) : ?>
            <?php foreach ($ppcart_pages['links'] as $ppcart_link) : ?>
                <?php echo wp_kses_post($ppcart_link); ?>
            <?php endforeach; ?>
        <?php else : ?>
            <span class="ppcart-shop-pagination-status">
                <?php
                /* translators: 1: current page number, 2: number of pages. */
                echo esc_html(sprintf(__('Page %1$d of %2$d', 'publishpress-cart'), $ppcart_pages['current'], $ppcart_pages['total']));
                ?>
            </span>
        <?php endif; ?>
        <?php if ($ppcart_pages['next']) : ?>
            <a class="page-numbers next" href="<?php echo esc_url($ppcart_pages['next']); ?>" rel="next"><?php esc_html_e('Next', 'publishpress-cart'); ?> <span aria-hidden="true">→</span></a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
