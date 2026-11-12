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
        // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- reCAPTCHA siteverify can take longer than 3 seconds.
        'timeout' => 10,
    ]
);

if (is_wp_error($response)) {
    global $ppcart_debug_logger;
    if (is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_debug')) {
        $ppcart_debug_logger->log_debug('reCAPTCHA siteverify request failed: ' . $response->get_error_message(), 4);
    }
    return [ 'error-codes' => [ 'bad-request' ] ];
}

return json_decode(wp_remote_retrieve_body($response), true);
