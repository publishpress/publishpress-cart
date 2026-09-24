<?php

if (! defined('ABSPATH')) {
    exit;
}


?>
<div class="wrap cart-extensions-wrap">
    <div class="pp-columns-wrapper pp-enable-sidebar">
        <div class="pp-column-left">
            <h1><?php echo esc_html(sprintf('%s Extensions', apply_filters('ppcart_plugin_title', $this->plugin_title))); ?></h1>
            <div class="ppcart-reports">
                <?php foreach ($this->get_extensions() as $product) {
                    $extension_action = $this->get_extension_action($product);
                    ?>
                    <div data-wp-c16t="true" data-wp-component="Card" id="product-<?php echo esc_attr($product['id']); ?>" tabindex="-1" class="components-surface components-card">
                        <div class="product-card__content">
                            <div class="product-card__content-info">
                                <div class="product-card__header">
                                    <div class="product-card__details">
                                        <?php if (! empty($product['icon'])) { ?>
                                            <img class="product-card__icon" src="<?php echo esc_url($product['icon']); ?>" alt="<?php echo esc_attr($product['name']); ?>">
                                        <?php } ?>

                                        <div class="product-card__meta">
                                            <h2 class="product-card__title"><?php echo esc_html($product['name']); ?></h2>
                                            <p class="product-card__vendor-details">
                                                <span class="product-card__vendor">
                                                    <span>By </span>
                                                    <a href="https://publishpress.com/publishpress-cart/" target="_blank" rel="noopener noreferrer">PublishPress Cart</a>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <p class="product-card__description"><?php echo esc_html($product['description']); ?></p>
                            </div>
                            <div class="product-card__divider">
                                <hr>
                            </div>

                            <div class="product-card__footer">
                                <div class="product-card__price">
                                    <span class="product-card__price-label">
                                        <a href="<?php echo esc_url($extension_action['url']); ?>"<?php echo (! empty($extension_action['new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="<?php echo esc_attr($extension_action['class']); ?>"><?php echo esc_html($extension_action['label']); ?></a>
                                    </span>
                                    <span class="product-card__price-billing" aria-hidden="true"></span>
                                </div>
                                <div class="product-card__rating"></div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
        if (function_exists('ppcart_render_admin_sidebar')) {
            ppcart_render_admin_sidebar();
        }
?>
    </div>
</div>
