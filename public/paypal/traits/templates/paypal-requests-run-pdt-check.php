<?php

if (! defined('ABSPATH')) {
    exit;
}


// check subscription (if exists) to see if this is an upsell
$cart_order = new PPCart_Order($order_info['ID']);
$sub = false;
if (isset($order_info['subscription_id']) && $order_info['subscription_id']) {
    $sub = new PPCart_Subscription($order_info['subscription_id']);
}

$response = ppcart_safe_remote_post(
    $paypal_data['paypalUrl'],
    [
        'body'    => [
            'cmd'    => '_notify-synch',
            'tx'     => $paypal_data['tx'],
            'at'     => $paypal_data['paypalPDT'],
            'submit' => 'PDT',
        ],
        'timeout' => 3,
    ]
);

if (is_wp_error($response)) {
    return false;
}

$response = wp_remote_retrieve_body($response);
$response_array = preg_split('/\r\n|\r|\n/', $response);
$final_data = [];
if (! isset($response_array[0]) || 'SUCCESS' !== $response_array[0]) {
    return false;
}

unset($response_array[0]);
foreach ($response_array as $data) {
    $key_value = explode('=', $data);
    if (isset($key_value[1])) {
        $final_data[$key_value[0]] = $key_value[1];
    }
}

$subscription_id = false;

// Read PublishPress Cart order/subscription IDs from custom meta.
if (isset($final_data['custom'])) {
    $custom = urldecode($final_data['custom']);
    $subscription_id = false;

    if (is_numeric($custom) || strpos($custom, '=') !== false) { // deprecated
        $custom_id  = explode("=", $custom);
        $order_id = $custom_id[0];
        if (isset($custom_id[1])) {
            $subscription_id = $custom_id[1];
        }
    } else {
        $custom = json_decode($custom);
        $order_id = $custom->order_id;
        if (isset($custom->subscription_id)) {
            $subscription_id = $custom->subscription_id;
        }
    }

    if (!$cart_order || $cart_order->id != $order_id) {
        $cart_order = new PPCart_Order($order_id);
    }

    $cart_order->transaction_id = $final_data['txn_id'];
    $cart_order->store();

    if ($subscription_id) {
        if (!$sub || $sub->id != $subscription_id) {
            $sub = new PPCart_Subscription($subscription_id);
        }
        $sub->subscription_id = $final_data['subscr_id'];
        $sub->store();
    }
}

if (!empty($final_data['payment_status'])) {
    $payment_status = strtolower($final_data['payment_status']);

    if ($payment_status == 'completed') {
        $cart_order->status = 'paid';
        $cart_order->payment_status = $final_data['payment_status'];
        $cart_order->store();

        if ($subscription_id) {
            $sub->status = 'active';
            $sub->sub_status = 'payment received';
            $sub->store();
        }

        return true;
    }
}

return false;
