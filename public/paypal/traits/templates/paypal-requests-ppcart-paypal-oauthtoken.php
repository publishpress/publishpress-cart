<?php

if (! defined('ABSPATH')) {
    exit;
}


$enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
if (get_option('_ppcart_paypal_enable_sandbox') == 'enable') {
    $paypalurl =  'https://api-m.sandbox.paypal.com/v1/oauth2/token';
    $clientID = get_option('_ppcart_paypal_sandbox_client_id');
    $secret = ppcart_get_sensitive_option('_ppcart_paypal_sandbox_secret');
} else {
    $paypalurl = 'https://api-m.paypal.com/v1/oauth2/token';
    $clientID = get_option('_ppcart_paypal_client_id');
    $secret = ppcart_get_sensitive_option('_ppcart_paypal_secret');
}

$response = ppcart_safe_remote_post(
    $paypalurl,
    [
        'headers' => [
            'Accept'        => 'application/json',
            'Accept-Language' => 'en_US',
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($clientID . ':' . $secret),
        ],
        'body'    => 'grant_type=client_credentials',
        'timeout' => 3,
    ]
);

if (is_wp_error($response)) {
    echo esc_html('Error:' . $response->get_error_message());
    return null;
}

$results = json_decode(wp_remote_retrieve_body($response));
return $results->access_token ?? null;
