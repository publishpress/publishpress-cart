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

function ppcart_get_shopping_cart(): PPCart_Shopping_Cart_Priced
{
    return (new PPCart_Shopping_Cart_Store())->read();
}

/**
 * Store an extension value on the current visitor's cart. A null value removes the key.
 * Keys that start with an underscore are reserved.
 */
function ppcart_shopping_cart_set_meta(string $key, mixed $value): PPCart_Shopping_Cart_Priced|WP_Error
{
    return (new PPCart_Shopping_Cart_Store())->update(static fn (PPCart_Shopping_Cart $cart): true|WP_Error => $cart->set_meta($key, $value));
}
