<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying an order bump on an order form
 * This template can be overridden by copying it to yourtheme/publishpress-cart/order-form/bump.php.
 */

global $ppcart_product;

$background_color = sanitize_hex_color($args['background_color'] ?? '');
$image_url = esc_url_raw($args['image'] ?? '');
$show_top_image = $image_url && 'top' === ($args['image_pos'] ?? '');
$row_class = $args['atts'] ?? '';
$image_style = '';

if ($image_url && ! $show_top_image) {
    $row_class = 'has-thumb';
    $css_url = str_replace([ '"', "'", '(', ')' ], [ '%22', '%27', '%28', '%29' ], $image_url);
    $image_style = safecss_filter_attr('background-image: url(' . $css_url . ')');
}
?>

<div id="ppcart-orderbump-<?php echo esc_attr($args['key']); ?>" class="ppcart-section orderbump"<?php if ($background_color) : ?> style="background-color: <?php echo esc_attr($background_color); ?>; border: 0"<?php endif; ?>>

    <h3 class="title"<?php if ($background_color) : ?> style="background: transparent"<?php endif; ?>><?php echo esc_html($args['headline']); ?></h3>

    <?php if ($show_top_image) : ?>
        <div class="ppcart-bump-image"><img src="<?php echo esc_url($image_url); ?>" alt=""></div>
    <?php endif; ?>

    <div class="ppcart-row <?php echo esc_attr($row_class); ?>"<?php if ($image_style) : ?> style="<?php echo esc_attr($image_style); ?>"<?php endif; ?>>
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
