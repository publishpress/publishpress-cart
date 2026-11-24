<?php

if (! defined('ABSPATH')) {
    exit;
}

function ppcart_product_shows_in_shop(int $product_id): bool
{
    return 'no' !== get_post_meta($product_id, '_ppcart_show_in_shop', true);
}

function ppcart_render_shop($attributes = []): string
{
    wp_enqueue_style('ppcart-shopping-cart', PPCART_BASE_URL . 'public/css/ppcart-shopping-cart.css', [], PPCART_VERSION);
    return (new PPCart_Shop())->render((array) $attributes);
}
