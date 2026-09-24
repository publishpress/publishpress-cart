<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Debug_Logger::get_current_flow_id().

if (! empty(self::$current_flow_id)) {
    return self::$current_flow_id;
}

$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$request_uri    = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$request_time   = filter_input(INPUT_SERVER, 'REQUEST_TIME_FLOAT', FILTER_VALIDATE_FLOAT);
$request_method = false === $request_method || null === $request_method ? '' : $request_method;
$request_uri    = false === $request_uri || null === $request_uri ? '' : $request_uri;
$request_time   = false === $request_time || null === $request_time ? microtime(true) : $request_time;
if (function_exists('wp_unslash')) {
    $request_method = wp_unslash($request_method);
    $request_uri    = wp_unslash($request_uri);
}

$seed = [
    (string) $request_time,
    sanitize_key($request_method),
    sanitize_text_field($request_uri),
    wp_generate_uuid4(),
];

self::$current_flow_id = substr(hash('sha256', implode('|', $seed)), 0, 16);
return self::$current_flow_id;
