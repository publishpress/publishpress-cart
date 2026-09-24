<?php

if (!defined('ABSPATH')) {
    exit;
}

$context = is_array($context) ? $context : [];

global $ppcart_debug_logger;
if (is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_event')) {
    $ppcart_debug_logger->log_event('stripe_connect_' . sanitize_key((string) $event), 'Stripe Connect debug', $context, $level);
    return;
}

if (defined('WP_DEBUG') && WP_DEBUG) {
    $context_json = wp_json_encode($context);
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug fallback only.
    error_log('[PublishPress Cart Stripe Connect] ' . sanitize_key((string) $event) . ' ' . (string) $context_json);
}
