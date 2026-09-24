<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_currency;

$enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
$paypalUrl = ($enableSandbox != 'disable') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

$business = ($enableSandbox != 'disable') ? get_option('_ppcart_paypal_sandbox_email') : get_option('_ppcart_paypal_email');

// Grab the post data so that we can set up the query string for PayPal.
// Ideally we'd use a whitelist here to check nothing is being injected into
// our post data.

$data = [
    "cmd"           => "_xclick",
    "no_note"       => "1",
    "bn"            => "PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest",
    "first_name"    => $order->first_name,
    "last_name"     => $order->last_name,
    "payer_email"   => $order->email,
    "item_number"   => $order->product_id,
    "submit"        => "Submit Payment",

    // Set the PayPal account.
    'business'      => $business,

    // Set the details about the product being purchased, including the amount
    // and currency so that these aren't overridden by the form data.
    'item_name'     => $order->product_name,
    'currency_code' => strtoupper($ppcart_currency),

    // Add any custom fields for the query string.
    'custom'        => wp_json_encode(apply_filters('ppcart_paypal_custom_payment_vars', ['order_id' => $order->id], $order->get_data(), false)),

    // Set the PayPal return addresses.
    'cancel_return' => stripslashes($order->cancel_url),
    'notify_url'    => stripslashes(get_site_url() . '/ppcart-webhook/paypal'),
    'return'        => stripslashes($order->return_url),
];

if (!empty($sub)) {
    $sub_amount = $sub->sub_amount;

    $sub_amount = number_format((float)$sub_amount, 2, '.', '');
    $data['cmd'] = "_xclick-subscriptions";
    $data['bn'] = "PP-SubscriptionsBF:btn_donateCC_LG.gif:NonHosted";
    $data['no_shipping'] = "1";
    $data['tax'] = $sub->tax_rate;
    $data['p3'] = $sub->sub_frequency;
    $data['t3'] = strtoupper(substr($sub->sub_interval, 0, 1));
    $data['a3'] = $sub_amount;
    $data['src'] = "1";
    if ($sub->sub_installments > 0) {
        $data["srt"] = $sub->sub_installments;
    }
    $custom = apply_filters('ppcart_paypal_custom_payment_vars', [
                'order_id' => $order->id,
                'subscription_id' => $sub->id,
            ], $order->get_data(), $sub->get_data());
    $data['custom'] = wp_json_encode($custom);

    if ($sub->free_trial_days || !$sub->free_trial_days && $order->amount != $sub_amount) {
        $data['a1'] = $order->amount;
        if (!$sub->free_trial_days) {
            // add trial period and reduce # of installments by 1
            if (isset($data["srt"])) {
                $data["srt"] -= 1;
            }
            $data['p1'] = $sub->sub_frequency;
            $data['t1'] = strtoupper(substr($sub->sub_interval, 0, 1));
        } else {
            $data['p1'] = $sub->free_trial_days;
            $data['t1'] = 'D';
        }
    }
} else {
    $data['amount'] = $order->amount;
}

$address_fields = ['address1','address2','city','state','zip','country'];
$data["address_override"] = "1";
foreach ($address_fields as $info) {
    if ($info != 'address2' && !isset($order->$info)) {
        unset($data["address_override"]);
        break;
    } else {
        $data[$info] = $order->$info;
    }
}
$data['lc'] = !empty($order->country) ? $order->country : get_option('_ppcart_country', 'US');
// Build the query string from the data.
$data = apply_filters('ppcart_paypal_payment_vars', $data, $order, $sub);

$queryString = http_build_query($data);

// Redirect to paypal IPN
return $paypalUrl . '?' . $queryString;
