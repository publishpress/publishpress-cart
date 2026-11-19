<?php

if (! defined('ABSPATH')) {
    exit;
}


$remote_addr = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
$ip = is_string($remote_addr) ? $remote_addr : '';
$postvars = ["secret" => $gsecretk, "response" => $recaptcha, "remoteip" => $ip];
$url = "https://www.google.com/recaptcha/api/siteverify";
$response = ppcart_safe_remote_post(
    $url,
    [
        'body'    => $postvars,
        'timeout' => 3,
    ]
);

if (is_wp_error($response)) {
    return [ 'error-codes' => [ 'bad-request' ] ];
}

return json_decode(wp_remote_retrieve_body($response), true);
