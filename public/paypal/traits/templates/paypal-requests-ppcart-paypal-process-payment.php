<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product;
$query_ppcart_pp = ppcart_filter_input(INPUT_GET, 'ppcart-pp', FILTER_VALIDATE_INT);
$query_ppcart_order = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);
$query_ppcart_pid = ppcart_filter_input(INPUT_GET, 'ppcart-pid', FILTER_VALIDATE_INT);
$query_ppcart_oto = ppcart_filter_input(INPUT_GET, 'ppcart-oto', FILTER_VALIDATE_INT);
$query_ppcart_oto_2 = ppcart_filter_input(INPUT_GET, 'ppcart-oto-2', FILTER_VALIDATE_INT);
$query_tx = filter_input(INPUT_GET, 'tx', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$query_wlfrom = filter_input(INPUT_GET, 'wlfrom', FILTER_SANITIZE_URL);
$query_ppcart_access = ppcart_filter_input(INPUT_GET, 'ppcart-access', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$query_ppcart_access = is_string($query_ppcart_access) ? sanitize_text_field($query_ppcart_access) : '';

// handle wishlist member redirect url
if (! empty($query_wlfrom)) {
    $url_components = wp_parse_url($query_wlfrom);
    parse_str($url_components['query'], $params);

    if (isset($params['ppcart-pp']) && isset($params['ppcart-order'])) {
        $query_ppcart_pp = absint($params['ppcart-pp']);
        $query_ppcart_order = absint($params['ppcart-order']);
        $query_ppcart_pid = isset($params['ppcart-pid']) ? absint($params['ppcart-pid']) : $query_ppcart_pid;
        $query_ppcart_oto = isset($params['ppcart-oto']) ? absint($params['ppcart-oto']) : $query_ppcart_oto;
        $query_ppcart_oto_2 = isset($params['ppcart-oto-2']) ? absint($params['ppcart-oto-2']) : $query_ppcart_oto_2;
        $query_tx = isset($params['tx']) ? sanitize_text_field($params['tx']) : $query_tx;
        if (isset($params['ppcart-access'])) {
            $query_ppcart_access = sanitize_text_field((string) $params['ppcart-access']);
        }
    }
}

// add tracking/redirect for initial charge
if ($query_ppcart_pp && $query_ppcart_order) {
    $enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
    $paypalPDT = ($enableSandbox != 'disable') ? ppcart_get_sensitive_option('_ppcart_paypal_sandbox_pdt_token') : ppcart_get_sensitive_option('_ppcart_paypal_pdt_token');
    $paypalUrl = ($enableSandbox != 'disable') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
    $order_id = $query_ppcart_order;

    if (! is_object($ppcart_product) || ! isset($ppcart_product->ID) || (int) $ppcart_product->ID !== (int) $query_ppcart_pid) {
        $ppcart_product_id = $query_ppcart_pid;
        $ppcart_product = ppcart_setup_product($ppcart_product_id);
    }

    $order_info = (array) ppcart_setup_order($order_id);
    if (! $query_ppcart_oto) {
        $_POST['purchase_amount'] = $order_info['amount'];
        $_POST['ppcart_order_id'] = $order_id;
        if (isset($order_info['order_bumps'])) {
            $_POST['ppcart-orderbump'] = $order_info['order_bumps'];
        }
    }

    if ($query_ppcart_oto_2) {
        $pdt_order = (array) ppcart_setup_order($query_ppcart_oto_2);
    } elseif ($query_ppcart_oto) {
        $pdt_order = (array) ppcart_setup_order($query_ppcart_oto);
    } else {
        $pdt_order = $order_info;
    }

    $pdt_verified = false;
    if (ppcart_paypal_pdt_is_configured()) {
        if (! empty($query_tx)) {
            $paypal_data = [
                'paypalPDT' => $paypalPDT,
                'tx'        => $query_tx,
                'paypalUrl' => $paypalUrl,
            ];
            $pdt_verified = (bool) $this->run_pdt_check($pdt_order, $paypal_data);
        }
    }

    if (
        ! ppcart_checkout_completion_allowed(
            $order_id,
            'paypal',
            [
                'access'       => $query_ppcart_access,
                'pdt_verified' => $pdt_verified,
            ]
        )
    ) {
        return;
    }

    if (apply_filters('ppcart_paypal_after_checkout_complete', false, $ppcart_product, $order_info, $query_ppcart_oto)) {
        return;
    }

    ppcart_maybe_fire_checkout_complete($order_id, $ppcart_product, 0);

    $product_id = (is_object($ppcart_product) && isset($ppcart_product->ID))
        ? (int) $ppcart_product->ID
        : (int) $query_ppcart_pid;

    if (is_object($ppcart_product) && isset($ppcart_product->redirect_url)) {
        $return_url = ppcart_personalize($ppcart_product->redirect_url, $order_info, 'urlencode');
    } else {
        $return_url = is_object($ppcart_product) && isset($ppcart_product->form_action)
            ? ppcart_personalize($ppcart_product->form_action, $order_info, 'urlencode')
            : home_url('/');
    }

    $return_url = apply_filters('ppcart_host_purchase_url', $return_url, $order_id, $product_id);

    $return_url = add_query_arg(
        [
            'ppcart-pid' => $product_id,
        ],
        PPCart_Order::confirmation_url($return_url, $order_id)
    );
    ppcart_redirect($return_url);
    exit;
}
