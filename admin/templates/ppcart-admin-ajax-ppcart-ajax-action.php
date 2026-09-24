<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! isset($_POST['ppcart_ajax_nonce'])) {
    wp_send_json_error([ 'message' => 'Missing Fields' ], 400);
}

$ajax_nonce = sanitize_text_field(wp_unslash($_POST['ppcart_ajax_nonce']));
if (! wp_verify_nonce($ajax_nonce, $this->plugin_name . '_admin_ajax')) {
    wp_send_json_error([ 'message' => 'Invalid Request' ], 401);
}

if (! current_user_can('manage_options')) {
    wp_send_json_error([ 'message' => 'Insufficient permissions' ], 403);
}

if (! isset($_POST['ppcart_action'])) {
    wp_send_json_error([ 'message' => 'Missing Fields' ], 400);
}

$ppcart_action = sanitize_key(wp_unslash($_POST['ppcart_action']));
if ('' === $ppcart_action) {
    wp_send_json_error([ 'message' => 'Invalid Action' ], 400);
}

$request_data = $this->sanitize_ajax_request_data($_POST, $ppcart_action);
$method = 'ppcart_' . $ppcart_action;
if (method_exists($this, $method)) {
    $data = $this->$method($request_data);
} else {
    $data = apply_filters('ppcart_admin_ajax_dispatch', null, $ppcart_action, $request_data, $this);
    if (null === $data) {
        $data = $this->$method($request_data);
    }
}
if (! is_array($data) || ! isset($data['response'])) {
    wp_send_json_error([ 'message' => 'Invalid Response' ], 500);
}

$response = $data['response'];
$response_code = isset($data['response_code']) ? absint($data['response_code']) : 200;
if ($response_code < 100 || $response_code > 599) {
    $response_code = 200;
}

wp_send_json($response, $response_code);
