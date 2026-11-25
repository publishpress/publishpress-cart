<?php

if (! defined('ABSPATH')) {
    exit;
}

$sections = [
    'shop-cart-general' => [
        'title' => __('General', 'publishpress-cart'),
        'fields' => [
            'enable' => ['checkbox', __('Enable shopping cart', 'publishpress-cart'), '_ppcart_shopping_cart_enable', 1],
        ],
    ],
    'shop-cart-pages' => [
        'title' => __('Pages', 'publishpress-cart'),
        'fields' => [
            'shop-page' => ['select', __('Shop page', 'publishpress-cart'), '_ppcart_shop_page_id', 0, ['page_select' => true, 'create_page' => ['role' => 'shop', 'title' => __('Shop', 'publishpress-cart'), 'block' => 'publishpress-cart/shop', 'attributes' => []]]],
            'cart-page' => ['select', __('Cart page', 'publishpress-cart'), '_ppcart_cart_page_id', 0, ['page_select' => true, 'create_page' => ['role' => 'cart', 'title' => __('Cart', 'publishpress-cart'), 'block' => 'publishpress-cart/cart', 'attributes' => []]]],
            'checkout-page' => ['select', __('Checkout page', 'publishpress-cart'), '_ppcart_cart_checkout_page_id', 0, ['page_select' => true, 'create_page' => ['role' => 'checkout', 'title' => __('Checkout', 'publishpress-cart'), 'block' => 'publishpress-cart/checkout-form', 'attributes' => ['pid' => 'cart']]]],
            'create-pages' => ['shopping_cart_pages', __('Create missing pages', 'publishpress-cart'), '_ppcart_shopping_cart_create_pages', '', ['skip_register' => true]],
        ],
    ],
    'shop-cart-add' => [
        'title' => __('Add to cart', 'publishpress-cart'),
        'fields' => [
            'after-add' => ['select', __('After add to cart', 'publishpress-cart'), '_ppcart_after_add_to_cart', 'mini_cart', ['selections' => ['mini_cart' => __('Open the mini cart', 'publishpress-cart'), 'cart_page' => __('Go to the Cart page', 'publishpress-cart'), 'checkout' => __('Go to checkout', 'publishpress-cart')]]],
        ],
    ],
    'shop-cart-fields' => [
        'title' => __('Checkout form', 'publishpress-cart'),
        'fields' => [
            'phone' => ['checkbox', __('Show phone field', 'publishpress-cart'), '_ppcart_cart_checkout_phone', 0],
            'address' => ['checkbox', __('Show address fields', 'publishpress-cart'), '_ppcart_cart_checkout_address', 0],
            'terms' => ['checkbox', __('Terms checkbox', 'publishpress-cart'), '_ppcart_cart_checkout_terms', 0],
            'terms-url' => ['text', __('Terms URL', 'publishpress-cart'), '_ppcart_cart_checkout_terms_url', '', ['type' => 'url']],
            'pay-button' => ['text', __('Pay button text', 'publishpress-cart'), '_ppcart_cart_checkout_button_text', __('Pay {total}', 'publishpress-cart')],
        ],
    ],
    'shop-cart-confirmation' => [
        'title' => __('After payment', 'publishpress-cart'),
        'fields' => [
            'confirmation' => ['select', __('Confirmation', 'publishpress-cart'), '_ppcart_cart_confirmation', 'message', ['selections' => ['message' => __('Show a message', 'publishpress-cart'), 'page' => __('Show a page', 'publishpress-cart'), 'url' => __('Redirect to a URL', 'publishpress-cart')]]],
            'confirmation-page' => ['select', __('Confirmation page', 'publishpress-cart'), '_ppcart_cart_confirmation_page', 0, ['page_select' => true]],
            'redirect' => ['text', __('Confirmation URL', 'publishpress-cart'), '_ppcart_cart_redirect', '', ['type' => 'url']],
            'message' => ['textarea', __('Confirmation message', 'publishpress-cart'), '_ppcart_cart_confirmation_message', __('Thank you. Check your email for your receipt.', 'publishpress-cart')],
        ],
    ],
    'shop-cart-storage' => [
        'title' => __('Storage', 'publishpress-cart'),
        'fields' => [
            'lifetime' => ['text', __('Keep carts for (days)', 'publishpress-cart'), '_ppcart_shopping_cart_lifetime', 7, ['type' => 'number', 'min' => 1]],
        ],
    ],
];

$options = [];
foreach ($sections as $section => $definition) {
    foreach ($definition['fields'] as $key => $field) {
        $options[$section]['shopping-cart-' . $key] = [
            'type' => $field[0],
            'label' => $field[1],
            'tab' => 'shop_cart',
            'section_title' => $definition['title'],
            'settings' => array_merge(['id' => $field[2], 'value' => $field[3]], $field[4] ?? []),
        ];
    }
}
return $options;
