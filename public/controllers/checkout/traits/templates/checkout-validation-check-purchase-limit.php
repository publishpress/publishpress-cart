<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_debug_logger;
$nonce = isset($_POST['ppcart-nonce']) && is_string($_POST['ppcart-nonce']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the nonce field for immediate verification.
    ? sanitize_text_field(wp_unslash($_POST['ppcart-nonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The value is verified immediately below.
    : '';

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    wp_send_json_error(
        [
            'error' => __('Invalid Request', 'publishpress-cart'),
        ]
    );
}

$product_id_in = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$product_id_in = (false !== $product_id_in && null !== $product_id_in) ? absint($product_id_in) : 0;

$ppcart_debug_logger->log_event(
    'checkout.purchase_limit.checking',
    'Checking customer purchase limit for this product.',
    [
        'product_id' => $product_id_in,
        'has_limit'  => isset($ppcart_product->customer_purchase_limit),
    ]
);

if (! $product_id_in) {
    wp_send_json_error(
        [
            'error' => __('There was a problem with your submission, please refresh the page and try again.', 'publishpress-cart'),
        ]
    );
}

if (!$ppcart_product || (int) $ppcart_product->ID !== $product_id_in) {
    $product_id = $product_id_in;
    $ppcart_product = ppcart_setup_product($product_id);
} else {
    $product_id = $ppcart_product->ID;
}

$purchase_limit = ppcart_check_product_purchase_limit(
    (int) $product_id,
    [ 'email' => (string) filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ],
    is_object($ppcart_product) ? $ppcart_product : null
);
if (is_wp_error($purchase_limit)) {
    $ppcart_debug_logger->log_debug('Error: current user ID: ' . get_current_user_id() . ' has already purchased product ID: ' . $product_id, 4);

    wp_send_json_error([ 'error' => $purchase_limit->get_error_message() ]);
}
