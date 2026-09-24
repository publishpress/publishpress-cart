<?php

if (! defined('ABSPATH')) {
    exit;
}

global $ppcart_product, $post;

/**
     * Adds dynamic checkout CSS even when a shortcode renders after wp_head().
     *
     * @param string $css CSS generated from checkout settings.
     */
function ppcart_enqueue_checkout_inline_style($css)
{
    ppcart_enqueue_or_print_inline_style('ppcart', $css, 'ppcart-checkout-inline');
}


require_once __DIR__ . '/functions/checkout-core.php';
require_once __DIR__ . '/functions/plan-coupon-fields.php';
require_once __DIR__ . '/functions/payment-address.php';
require_once __DIR__ . '/functions/summary-and-scripts.php';
