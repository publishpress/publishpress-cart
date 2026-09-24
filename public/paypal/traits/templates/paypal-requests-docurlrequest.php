<?php

if (! defined('ABSPATH')) {
    exit;
}


$response = ppcart_safe_remote_post(
    $paypalUrl,
    [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->paypal_oauthtoken(),
        ],
        'body'    => ! empty($args) ? wp_json_encode($args) : '',
        'timeout' => 3,
    ]
);

if (is_wp_error($response)) {
    echo esc_html('Error:' . $response->get_error_message());
    return null;
}

$results = json_decode(wp_remote_retrieve_body($response));

return $results;
