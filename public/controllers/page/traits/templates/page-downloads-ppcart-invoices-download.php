<?php

if (! defined('ABSPATH')) {
    exit;
}


// Pro redefines PPCART_BASE_FILE to the Pro bootstrap. Use the Free plugin directory.
$invoice_pdf = defined('PPCART_BASE_DIR')
    ? PPCART_BASE_DIR . 'public/partials/invoice-pdf.php'
    : dirname(__DIR__, 4) . '/partials/invoice-pdf.php';

if (get_query_var('ppcart-invoice') && file_exists($invoice_pdf)) {
    $download_request = ppcart_filter_input_request('dl', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $request_id       = ppcart_filter_input_request('id', FILTER_VALIDATE_INT);

    $invoice_query   = absint(get_query_var('ppcart-invoice'));
    $order_post_id   = absint($request_id ? $request_id : $invoice_query);
    $download        = null === $download_request ? true : (bool) $download_request;
    $cart_order      = false;
    $token_valid     = false;
    $access_allowed  = false;

    if ($order_post_id) {
        $cart_order = new PPCart_Order($order_post_id);
    }

    if ($cart_order && $cart_order->id) {
        $order_user_id = absint($cart_order->user_account);
        $is_admin      = current_user_can('manage_options');
        $is_owner      = is_user_logged_in() && (get_current_user_id() === $order_user_id);

        if ($is_admin || $is_owner) {
            $access_allowed = true;
        }

        $token_request = ppcart_filter_input_request('token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $token_request = is_string($token_request) ? trim($token_request) : '';
        $stored_token  = ppcart_get_post_meta($order_post_id, PPCart_Order::INVOICE_TOKEN_META_KEY, true);
        $stored_token  = is_string($stored_token) ? $stored_token : '';

        if ('' !== $token_request && '' !== $stored_token && (function_exists('hash_equals') ? hash_equals($stored_token, $token_request) : $stored_token === $token_request)) {
            $token_valid = true;
        }

        // Backward compatibility for old links, restricted to owner/admin only.
        if (! $token_valid && $request_id && $access_allowed) {
            $legacy_invoice_id = substr((string) absint(get_query_var('ppcart-invoice')), 0, -2);
            $timestamp         = $request_id . get_post_timestamp($request_id);

            if ($timestamp && '' !== $legacy_invoice_id && str_contains($timestamp, $legacy_invoice_id)) {
                $token_valid = true;
            }
        }

        if (0 === $order_user_id && $token_valid) {
            $access_allowed = true;
        }
    }

    if ($cart_order && $cart_order->id && $token_valid && $access_allowed) {
        $cart_order->output_invoice($download);
    }
}
