<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/functions-shopping-cart.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-catalog.php';
require_once __DIR__ . '/class-ppcart-shop.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-product-settings.php';

add_shortcode('ppcart_shop', 'ppcart_render_shop');
add_action('ppcart_after_validate_meta', [PPCart_Shopping_Cart_Product_Settings::class, 'save']);
