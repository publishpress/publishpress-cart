<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying an order bump on an order form
 * This template can be overridden by copying it to yourtheme/publishpress-cart/order-form/bump.php.
 */

global $ppcart_product;
?>

<div id="ppcart-orderbump-<?php echo esc_attr($args['key']); ?>" class="ppcart-section orderbump">

    <h3 class="title"><?php echo esc_html($args['headline']); ?></h3>

    <?php if ($args['image'] && $args['image_pos'] == 'top') : ?>
        <div class="ppcart-bump-image"><img src="<?php echo esc_url($args['image']); ?>"></div>
    <?php endif; ?>

    <div class="ppcart-row <?php echo esc_attr($args['atts']); ?>">
        <?php if (isset($ppcart_product->show_splitin)) : ?>
            <div class="ppcart-col-sm-12 ob-cta">
                <input
                        type="checkbox"
                        id="<?php echo esc_attr($args['cb_id']);?>"
                        name="ppcart-orderbump[<?php echo esc_attr($args['key']);?>]"
                        class="<?php echo esc_attr($args['class']); ?>"
                        value="<?php echo esc_attr($args['bump_id']);?>"
                        data-testid="<?php echo esc_attr(ppcart_testid('ppcart-checkout-order-bump-' . $args['key'])); ?>"
                    />
                <label for="<?php echo esc_attr($args['cb_id']);?>">
                    <span class="item-name"><?php echo esc_html($args['cta']); ?></span>
                    <span class="item-description"><?php echo wp_kses_post(wpautop(esc_html($args['text']))); ?></span>
                </label>
            </div>
        <?php else : ?>
            <div class="ppcart-col-sm-12">
                <?php echo wp_kses_post(wpautop(esc_html($args['text']))); ?>
            </div>
            <div class="ppcart-col-sm-12 ob-cta">
                <label>
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($args['cb_id']);?>"
                        name="ppcart-orderbump[<?php echo esc_attr($args['key']);?>]"
                        class="<?php echo esc_attr($args['class']); ?>"
                        value="<?php echo esc_attr($args['bump_id']);?>"
                        data-testid="<?php echo esc_attr(ppcart_testid('ppcart-checkout-order-bump-' . $args['key'])); ?>"
                    />
                    <span class="item-name">
                        <?php echo esc_html($args['cta']); ?>
                    </span>
                </label>
            </div>
        <?php endif; ?>
    </div>

</div>
