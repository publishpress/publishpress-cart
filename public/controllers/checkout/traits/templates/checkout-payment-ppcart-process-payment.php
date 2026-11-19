<?php

if (! defined('ABSPATH')) {
    exit;
}


if (defined('DOING_AJAX') && DOING_AJAX) {
    return;
}

global $ppcart_product;
$ppcart_oto_get             = ppcart_filter_input(INPUT_GET, 'ppcart-oto', FILTER_VALIDATE_INT);
$ppcart_oto2_get            = ppcart_filter_input(INPUT_GET, 'ppcart-oto-2', FILTER_VALIDATE_INT);
$ppcart_order_get           = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);
$step_get               = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT);
$ppcart_process_payment_get = ppcart_filter_input(INPUT_POST, 'ppcart_process_payment', FILTER_VALIDATE_INT);
$ppcart_product_id_post     = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$ppcart_order_id_post       = ppcart_filter_input(INPUT_POST, 'ppcart_order_id', FILTER_VALIDATE_INT);

$ppcart_oto_get             = (false !== $ppcart_oto_get && null !== $ppcart_oto_get) ? absint($ppcart_oto_get) : 0;
$ppcart_oto2_get            = (false !== $ppcart_oto2_get && null !== $ppcart_oto2_get) ? absint($ppcart_oto2_get) : 0;
$ppcart_order_get           = (false !== $ppcart_order_get && null !== $ppcart_order_get) ? absint($ppcart_order_get) : 0;
$step_get               = (false !== $step_get && null !== $step_get) ? absint($step_get) : 0;
$ppcart_process_payment_get = (false !== $ppcart_process_payment_get && null !== $ppcart_process_payment_get) ? absint($ppcart_process_payment_get) : 0;
$ppcart_product_id_post     = (false !== $ppcart_product_id_post && null !== $ppcart_product_id_post) ? absint($ppcart_product_id_post) : 0;
$ppcart_order_id_post       = (false !== $ppcart_order_id_post && null !== $ppcart_order_id_post) ? absint($ppcart_order_id_post) : 0;

// add tracking/redirect for upsell charge
if (apply_filters('ppcart_process_payment_upsell_flow', false, $ppcart_oto_get, $ppcart_oto2_get, $ppcart_order_get, $step_get)) {
    return;
}

// add tracking/redirect for initial charge
if (! $ppcart_process_payment_get) {
    if ($step_get) {
        $_POST['ppcart_process_payment'] = 1;
        $order_id = $ppcart_order_get;
        $ppcart_product_id = (ppcart_get_post_meta($order_id, 'product_id', true));
    } else {
        return;
    }
} else {
    $ppcart_product_id = $ppcart_product_id_post;
    $order_id = $ppcart_order_id_post;
}

if (! $order_id) {
    return;
}

$completion_context = $ppcart_process_payment_get ? 'post' : 'step';
if (! ppcart_checkout_completion_allowed($order_id, $completion_context, [])) {
    return;
}

$ppcart_product = ppcart_setup_product($ppcart_product_id);
$order_info = (array) ppcart_setup_order($order_id);

if (! $step_get || 1 === $step_get) {
    $_POST['ppcart_purchase_amount'] = $order_info['amount'];
}

// Show upsell
$show_upsell = apply_filters('ppcart_show_upsell', false, $order_info['ID'], $ppcart_product);
if ($show_upsell) {
    $_POST['ppcart_order'] = $order_info;
    $_POST['ppcart_upsell_nonce'] = wp_create_nonce('ppcart_upsell-' . $order_info['ID']);
    return;
}

ppcart_maybe_fire_checkout_complete($order_info['ID'], $ppcart_product, $step_get);

if (isset($ppcart_product->redirect_url)) {
    $redirect = esc_url_raw(ppcart_personalize($ppcart_product->redirect_url, $order_info));
    ppcart_redirect($redirect);
}

return;
