<?php

if (! defined('ABSPATH')) {
    exit;
}


$ppcart_order_get      = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);
$purchase_amount   = ppcart_filter_input(INPUT_POST, 'ppcart_purchase_amount', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// page redirect
$ppcart_id = intval(ppcart_get_post_meta(get_the_ID(), 'related_product', true));
if ($ppcart_id) {
    global $ppcart_product;
    $ppcart_product = ppcart_setup_product($ppcart_id);

    if (ppcart_is_cart_closed()) {
        $redirect = get_permalink($ppcart_id);
        if (isset($ppcart_product->checkout_ended_action) && 'redirect' === $ppcart_product->checkout_ended_action) {
            $configured = isset($ppcart_product->checkout_ended_redirect) ? trim((string) $ppcart_product->checkout_ended_redirect) : '';
            if ('' !== $configured) {
                $redirect = $configured;
            }
        }
        if (is_string($redirect) && '' !== $redirect) {
            ppcart_redirect($redirect);
        }
    }
}

// product page redirect
$post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
if (! in_array(get_post_type(), $post_types, true) || $ppcart_order_get || (is_string($purchase_amount) && '' !== $purchase_amount)) {
    return;
}

$checkout_ended_action = ppcart_get_post_meta(get_the_ID(), 'checkout_ended_action', true);
if (ppcart_is_cart_closed(get_the_ID())) {
    if ($checkout_ended_action == 'redirect') {
        $redirect = trim((string) ppcart_get_post_meta(get_the_ID(), 'checkout_ended_redirect', true));
        if ('' !== $redirect) {
            ppcart_redirect(esc_url($redirect));
        }
    }
}

/**
 * Fires on a product page when Free did not redirect the request itself.
 *
 * Pro hooks this to redirect a product page that the merchant chose to hide.
 *
 * @param int $post_id Product post ID.
 */
do_action('ppcart_product_page_redirect', (int) get_the_ID());
