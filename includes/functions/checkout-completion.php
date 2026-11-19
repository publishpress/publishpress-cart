<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Times checkout completion has fired for an order.
 *
 * @param int $order_id Order post ID.
 * @return int
 */
function ppcart_checkout_complete_fire_count($order_id)
{
    $order_id = absint($order_id);
    if (! $order_id) {
        return 0;
    }

    return max(0, (int) ppcart_get_post_meta($order_id, 'checkout_complete_fired', true));
}

/**
 * Claim the one allowed checkout-complete fire for an order.
 *
 * @param int $order_id Order post ID.
 * @return bool True when this request won the first claim.
 */
function ppcart_claim_checkout_complete($order_id)
{
    $order_id = absint($order_id);
    if (! $order_id) {
        return false;
    }

    if (ppcart_checkout_complete_fire_count($order_id) > 0) {
        return false;
    }

    $claimed = add_post_meta($order_id, '_ppcart_checkout_complete_claimed', '1', true);
    if (! $claimed) {
        return false;
    }

    ppcart_update_post_meta($order_id, 'checkout_complete_fired', 1);

    return true;
}

/**
 * Whether PayPal PDT verification is configured for the active mode.
 *
 * @return bool
 */
function ppcart_paypal_pdt_is_configured()
{
    $enable_sandbox = get_option('_ppcart_paypal_enable_sandbox');
    $pdt_token      = ('disable' !== $enable_sandbox)
        ? ppcart_get_sensitive_option('_ppcart_paypal_sandbox_pdt_token')
        : ppcart_get_sensitive_option('_ppcart_paypal_pdt_token');

    return is_string($pdt_token) && '' !== $pdt_token;
}

/**
 * Read checkout access proof from GET/POST (not invoice `token` alone).
 *
 * @return string
 */
function ppcart_checkout_request_access_value()
{
    $access = ppcart_filter_input(INPUT_POST, 'ppcart-access', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (is_string($access) && '' !== trim($access)) {
        return sanitize_text_field($access);
    }

    $access = ppcart_filter_input(INPUT_GET, 'ppcart-access', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (is_string($access) && '' !== trim($access)) {
        return sanitize_text_field($access);
    }

    return '';
}

/**
 * Whether the visitor may complete checkout for an order (owner/admin bypass).
 *
 * @param int $order_id Order post ID.
 * @return bool
 */
function ppcart_checkout_completion_owner_or_admin($order_id)
{
    $order_id = absint($order_id);
    if (! $order_id) {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    $current_user_id = absint(get_current_user_id());
    $owner_id        = absint(ppcart_get_post_meta($order_id, 'user_account', true));

    return $current_user_id && $owner_id && $current_user_id === $owner_id;
}

/**
 * Whether checkout completion side effects may run for this request.
 *
 * @param int    $order_id Order post ID.
 * @param string $context  post|step|paypal
 * @param array  $args     Optional nonce, access, request_token, pdt_verified keys.
 * @return bool
 */
function ppcart_checkout_completion_allowed($order_id, $context, $args = [])
{
    $order_id = absint($order_id);
    if (! $order_id) {
        return false;
    }

    if (ppcart_checkout_completion_owner_or_admin($order_id)) {
        return true;
    }

    $access = isset($args['access']) ? sanitize_text_field((string) $args['access']) : '';
    if ('' === $access) {
        $access = ppcart_checkout_request_access_value();
    }

    $request_token = isset($args['request_token']) ? sanitize_text_field((string) $args['request_token']) : '';
    if ('' === $request_token && function_exists('ppcart_filter_input_request')) {
        $filtered = ppcart_filter_input_request('token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (is_string($filtered)) {
            $request_token = trim($filtered);
        }
    }

    $token_match = ('' !== $access && PPCart_Order::token_matches($order_id, $access))
        || ('' !== $request_token && PPCart_Order::token_matches($order_id, $request_token));

    if ('paypal' === $context) {
        if (ppcart_paypal_pdt_is_configured()) {
            return ! empty($args['pdt_verified']);
        }

        return $token_match;
    }

    if ('post' === $context) {
        $nonce = isset($args['nonce']) ? sanitize_text_field((string) $args['nonce']) : '';
        if ('' === $nonce) {
            $filtered = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $nonce    = is_string($filtered) ? sanitize_text_field($filtered) : '';
        }

        if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
            return false;
        }

        return $token_match;
    }

    if ('step' === $context) {
        return $token_match;
    }

    return false;
}

/**
 * Fire checkout completion once, or step-viewed when the claim is taken.
 *
 * @param int         $order_id Order post ID.
 * @param object|null $product  Product object.
 * @param int         $step     Funnel step from the request.
 * @return void
 */
function ppcart_maybe_fire_checkout_complete($order_id, $product, $step = 0)
{
    $order_id = absint($order_id);
    if (! $order_id) {
        return;
    }

    if (ppcart_claim_checkout_complete($order_id)) {
        do_action('ppcart_checkout_complete', $order_id, $product);

        return;
    }

    $step = absint($step);
    if ($step > 0) {
        do_action('ppcart_checkout_step_viewed', $order_id, $product, $step);
    }
}
