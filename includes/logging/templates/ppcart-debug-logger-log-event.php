<?php

if (! defined('ABSPATH')) {
    exit;
}


$event = strtolower(preg_replace('/[^a-z0-9_.-]/i', '', (string) $event));
$context = is_array($context) ? $this->redact_context($context) : [];
if ($event) {
    $context = array_merge([ 'event' => $event ], $context);
}
if (empty($context['flow_id'])) {
    $context['flow_id'] = $this->get_current_flow_id();
}

$encoded = function_exists('wp_json_encode') ? wp_json_encode($context) : json_encode($context); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for standalone tests.
$suffix  = $encoded ? ' context=' . $encoded : '';

$this->log_debug(rtrim((string) $message) . $suffix, $level, $section_break, $file_name);
