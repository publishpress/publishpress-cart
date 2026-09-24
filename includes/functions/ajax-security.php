<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get or set the single compatibility nonce verifier.
 *
 * @param callable|null $verifier Verifier callback, or null to clear it.
 * @param bool          $set      Whether to replace the current callback.
 * @return callable|null
 */
function ppcart_nonce_compatibility_verifier($verifier = null, $set = false)
{
    static $registered_verifier = null;

    if ($set) {
        $registered_verifier = is_callable($verifier) ? $verifier : null;
    }

    return $registered_verifier;
}

/**
 * Verify a canonical Cart nonce.
 *
 * Compatibility Mode can supply a positive result for a mapped legacy action
 * after the canonical action has failed. New nonces are always generated with
 * canonical action strings.
 *
 * @param string $nonce  Nonce value.
 * @param string $action Canonical nonce action.
 * @return int|false
 */
function ppcart_verify_nonce($nonce, $action)
{
    $result = wp_verify_nonce($nonce, $action);

    if (false !== $result) {
        return $result;
    }

    $compatibility_verifier = ppcart_nonce_compatibility_verifier();

    if (! is_callable($compatibility_verifier)) {
        return false;
    }

    return call_user_func($compatibility_verifier, false, $nonce, $action);
}

/**
 * Verify an AJAX nonce against the canonical action and gated legacy map.
 *
 * This mirrors check_ajax_referer() while delegating the actual verification
 * to ppcart_verify_nonce().
 *
 * @param string     $action    Canonical nonce action.
 * @param string|false $query_arg Request field containing the nonce.
 * @param bool       $stop      Whether to stop the request on failure.
 * @return int|false
 */
function ppcart_check_ajax_referer($action = -1, $query_arg = false, $stop = true)
{
    $nonce = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper checks for nonce fields before verifying them.
    if ($query_arg && isset($_REQUEST[ $query_arg ])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper sanitizes and verifies the nonce below.
        $nonce = sanitize_text_field(wp_unslash($_REQUEST[ $query_arg ]));
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper checks for nonce fields before verifying them.
    } elseif (isset($_REQUEST['_ajax_nonce'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper sanitizes and verifies the nonce below.
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['_ajax_nonce']));
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper checks for nonce fields before verifying them.
    } elseif (isset($_REQUEST['_wpnonce'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper sanitizes and verifies the nonce below.
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $result = ppcart_verify_nonce($nonce, $action);

    do_action('check_ajax_referer', $action, $result);

    if ($stop && false === $result) {
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            wp_die(-1, 403);
        }

        die('-1');
    }

    return $result;
}

/**
 * Verify an admin nonce against the canonical action and gated legacy map.
 *
 * This mirrors check_admin_referer() while delegating the actual verification
 * to ppcart_verify_nonce(). The query argument is the canonical request field
 * name (slice 26). Leftover field names work only while Compatibility Mode is on.
 *
 * @param string $action    Canonical nonce action.
 * @param string $query_arg Request field containing the nonce.
 * @return int|false
 */
function ppcart_check_admin_referer($action = -1, $query_arg = '_wpnonce')
{
    $nonce = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper checks for nonce fields before verifying them.
    if ($query_arg && isset($_REQUEST[ $query_arg ])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This helper sanitizes and verifies the nonce below.
        $nonce = sanitize_text_field(wp_unslash($_REQUEST[ $query_arg ]));
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $result = ppcart_verify_nonce($nonce, $action);

    do_action('check_admin_referer', $action, $result);

    if (false === $result) {
        if (function_exists('wp_nonce_ays')) {
            wp_nonce_ays($action);
        }

        wp_die(-1, 403);
    }

    return $result;
}
