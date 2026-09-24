<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product;

$nonce = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_product_id = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$cancel_url = filter_input(INPUT_POST, 'cancel_url', FILTER_SANITIZE_URL);

if (empty($nonce) || ! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    echo wp_json_encode([
        'error' => __("An error occurred, please refresh the page and try again.", "publishpress-cart"),
    ]);
    exit();
}

do_action('ppcart_before_create_main_order');
$is_sub = false;

// setup product info
$ppcart_product = ppcart_setup_product($ppcart_product_id);

// setup order info
$ppcart_order = new PPCart_Order();
$ppcart_order->load_from_post();
$ppcart_order = apply_filters('ppcart_after_order_load_from_post', $ppcart_order);

$ppcart_order->gateway_mode = get_option('_ppcart_paypal_enable_sandbox') != 'disable' ? 'test' : 'live';

if ($ppcart_order->plan->type == 'recurring') {
    $is_sub = true;
}

if (is_array($ppcart_order->order_bumps) && !$is_sub) {
    foreach ($ppcart_order->order_bumps as $bump) {
        // do order bumps have a subscription?
        if (isset($bump['plan']) && $bump['plan']->type == 'recurring') {
            $is_sub = true;
        }
    }
}

$sub = '';
if ($is_sub) {
    $sub = PPCart_Subscription::from_order($ppcart_order);
    $sub->store();
    $ppcart_order->subscription_id = $sub->id;
}

$order_id = $ppcart_order->store();
$ppcart_order->cancel_url = esc_url_raw((string) $cancel_url);
if (! empty($ppcart_product->upsell_path) && $ppcart_product->confirmation != 'redirect') {
    $return_url = $ppcart_product->form_action;
} else {
    $return_url = $ppcart_product->thanks_url;
}

$return_url = apply_filters('ppcart_host_purchase_url', $return_url, $order_id, $ppcart_product_id);

// Do not put the order-access token on this URL. PayPal treats `token` as its
// own checkout parameter, so a merchant `token=` here blocks the sandbox redirect.
$ppcart_order->return_url = add_query_arg(
    array_merge(
        [
            'ppcart-order' => $order_id,
            'ppcart-pid' => $ppcart_product_id,
            'ppcart-pp' => 1,
        ],
        PPCart_Order::access_arg($order_id)
    ),
    $return_url
);

$paypalUrl = $this->build_paypal_url($ppcart_order, $sub);
$paypalUrl = apply_filters('ppcart_paypal_checkout_url', $paypalUrl, $ppcart_order, $sub);
$res = ['url' => $paypalUrl];

echo wp_json_encode($res);
exit();
