<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying an archive item
 * This template can be overridden by copying it to <active-theme-folder>/publishpress-cart/archive/item.php.
 */

?>

<li class="cards__item">
    <a href="<?php the_permalink(); ?>" class="card" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-product-card-' . get_the_ID())); ?>">
        <?php if (has_post_thumbnail()) : ?>
        <div class="card__image">
            <?php the_post_thumbnail('medium_large'); ?>
        </div>
        <?php endif; ?>
        <div class="card__content">
            <div class="card__title"><?php echo esc_html(get_the_title()); ?></div>
            <div class="card__text"><?php echo wp_kses_post(get_the_excerpt()); ?></div>
            <span class="ppcart-btn ppcart-btn--block card__btn"><?php echo esc_html($attr['button_text']); ?></span>
        </div>
    </a>
</li>
