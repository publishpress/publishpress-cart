<?php

defined('ABSPATH') || exit;

/**
 * Count Stripe checkout requests before creating gateway or order records.
 *
 * @param bool $preparing Whether this is a checkout preparation request.
 */
function ppcart_check_stripe_checkout_rate_limit($preparing)
{
    global $wpdb, $ppcart_debug_logger;

    /**
     * Configure Stripe checkout request limits. Observe records the first
     * over-limit request per window. Enforce also rejects over-limit requests.
     */
    $mode = apply_filters('ppcart_stripe_checkout_rate_limit_mode', 'observe');
    if (! in_array($mode, ['observe', 'enforce'], true)) {
        return;
    }

    // Trust the address supplied by the web server, never a forwarding header.
    // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders -- Validated as an IP address before hashing.
    $address = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
    if (! $address) {
        return;
    }

    $bucket = $preparing ? 'preparation' : 'payment';
    $limit = (int) apply_filters('ppcart_stripe_checkout_rate_limit', $preparing ? 30 : 10, $bucket);
    if ($limit < 1 || $limit >= PHP_INT_MAX) {
        return;
    }

    $now = time();
    $expires = (intdiv($now, 60) + 1) * 60;
    $prefix = '_ppcart_checkout_rate_';
    $key = $prefix . sprintf('%010d', $expires) . '_' . $bucket . '_'
        . hash_hmac('sha256', inet_pton($address), wp_salt('nonce'));

    // Use the unique option name and conditional updates to reserve slots
    // atomically across PHP workers. These private rows bypass the option cache.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic counter reservation.
    $inserted = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'no')",
        $key
    ));

    if (1 === wp_rand(1, 20)) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded cleanup of expired private counters.
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name < %s ORDER BY option_name LIMIT 1000",
            $wpdb->esc_like($prefix) . '%',
            $prefix . sprintf('%010d', $now) . '_'
        ));
    }

    if (0 !== $inserted) {
        return;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic counter reservation.
    $reserved = $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = CAST(option_value AS UNSIGNED) + 1 WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
        $key,
        $limit
    ));
    if (0 !== $reserved) {
        return;
    }

    // Mark the first over-limit request so a burst does not flood the log.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic observation marker.
    $first_excess = $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = %s AND option_value = %s",
        $limit + 1,
        $key,
        (string) $limit
    ));
    if (1 === $first_excess && isset($ppcart_debug_logger)) {
        $ppcart_debug_logger->log_event(
            'checkout.security.rate_limit',
            'Stripe checkout request limit exceeded.',
            ['bucket' => $bucket, 'mode' => $mode, 'limit' => $limit]
        );
    }

    if ('enforce' === $mode) {
        // Checkout clients already handle this JSON envelope in their success callback.
        wp_send_json_error([
            'code' => 'ppcart_checkout_rate_limited',
            'error' => __('Too many checkout requests. Please wait a moment and try again.', 'publishpress-cart'),
            'retry_after' => max(1, $expires - time()),
        ]);
    }
}
