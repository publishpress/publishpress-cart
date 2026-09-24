<?php

if (! defined('ABSPATH')) {
    exit;
}


$raw_line = trim((string) $line);
if ('' === $raw_line || self::is_section_break($raw_line)) {
    return false;
}

$time_string = '';
$timestamp   = 0;
$level       = 'UNKNOWN';
$message     = $raw_line;

if (preg_match('/^\[(?P<time>[^\]]+)\]\s*-\s*(?P<level>[A-Z]+)\s*:\s*(?P<message>.*)$/', $raw_line, $matches)) {
    $time_string = trim($matches['time']);
    $timestamp   = self::parse_timestamp($time_string);
    $level       = self::normalize_level($matches['level']);
    $message     = trim($matches['message']);
}

$context = self::extract_structured_context($message);
if (isset($context['clean_message'])) {
    $message = $context['clean_message'];
    unset($context['clean_message']);
}

$json_payload = self::extract_json_payload($message);
if (isset($json_payload['clean_message']) && '' !== $json_payload['clean_message']) {
    $message = $json_payload['clean_message'];
}

$message = self::redact_text($message);

if (! empty($json_payload['data']) && is_array($json_payload['data'])) {
    $context = array_merge($context, $json_payload['data']);
}

$context    = self::redact_context($context);
$record_ids = self::detect_record_ids($message, $context);
$stripe_ids = self::detect_stripe_ids($raw_line, $context);
$workflow   = self::detect_workflow($message, $context);

return [
    'timestamp'       => $timestamp,
    'time_utc'        => $timestamp ? gmdate('c', $timestamp) : '',
    'time_display'    => $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : $time_string,
    'level'           => $level,
    'workflow'        => $workflow,
    'message'         => $message,
    'record'          => self::format_record_context($record_ids),
    'flow_id'         => self::detect_flow_id($context),
    'order_id'        => $record_ids['order_id'],
    'subscription_id' => $record_ids['subscription_id'],
    'product_id'      => $record_ids['product_id'],
    'stripe_ids'      => $stripe_ids,
    'context'         => $context,
    'raw'             => self::redact_text($raw_line),
];
