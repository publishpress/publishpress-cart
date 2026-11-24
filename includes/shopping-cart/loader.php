<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/functions-shopping-cart.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-settings.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-catalog.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-priced.php';
require_once __DIR__ . '/class-ppcart-shopping-cart.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-store.php';
require_once __DIR__ . '/class-ppcart-shop.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-product-settings.php';

add_shortcode('ppcart_shop', 'ppcart_render_shop');
add_action('ppcart_after_validate_meta', [PPCart_Shopping_Cart_Product_Settings::class, 'save']);

add_action('wp_login', [new PPCart_Shopping_Cart_Store(), 'merge_on_login'], 10, 2);
add_action('ppcart_shopping_cart_cleanup', [PPCart_Shopping_Cart_Store::class, 'delete_expired']);
