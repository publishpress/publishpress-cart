<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
if ('POST' !== strtoupper($ppcart_request_method)) {
    http_response_code(405);
    exit();
}

// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsRemoteFile -- Reads raw local request body from php://input.
$ppcart_paypal_event  = json_decode((string) file_get_contents('php://input'), true);
$ppcart_paypal_status = PPCart_Paypal_Webhook::handle(is_array($ppcart_paypal_event) ? $ppcart_paypal_event : []);

http_response_code($ppcart_paypal_status);
header('Content-Type: application/json; charset=utf-8');
echo '{}';
exit();
