<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/functions-shopping-cart.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-settings.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-catalog.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-priced-line.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-notice.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-priced.php';
require_once __DIR__ . '/class-ppcart-shopping-cart.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-store.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-view.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-rest.php';
require_once __DIR__ . '/class-ppcart-shop.php';
require_once __DIR__ . '/class-ppcart-shopping-cart-product-settings.php';

add_action('ppcart_after_validate_meta', [PPCart_Shopping_Cart_Product_Settings::class, 'save']);

$ppcart_shopping_cart_rest = new PPCart_Shopping_Cart_Rest();
add_action('rest_api_init', [$ppcart_shopping_cart_rest, 'register_routes']);
// Core cookie authentication runs at priority 100.
add_filter('rest_authentication_errors', [$ppcart_shopping_cart_rest, 'authenticate_cart_read'], 101);
add_filter('rest_post_dispatch', [$ppcart_shopping_cart_rest, 'send_no_store'], 10, 3);
add_action('wp_login', [new PPCart_Shopping_Cart_Store(), 'merge_on_login'], 10, 2);
add_action('ppcart_shopping_cart_cleanup', [PPCart_Shopping_Cart_Store::class, 'delete_expired']);
