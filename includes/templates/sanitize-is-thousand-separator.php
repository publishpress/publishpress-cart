<?php

if (! defined('ABSPATH')) {
    exit;
}


$raw_referer = ppcart_filter_input_request('_wp_http_referer', FILTER_SANITIZE_URL);
$raw_referer = is_string($raw_referer) ? $raw_referer : '';
$fallback    = admin_url('edit.php?post_type=' . (function_exists('ppcart_live_post_type') ? ppcart_live_post_type('product') : 'ppcart_product'));
$referer     = wp_validate_redirect($raw_referer, $fallback);

$parts = explode($thousand_sep, $price);

if (count($parts) > 1) {
    foreach ($parts as $k => $v) {
        if ($decimal_sep && strpos($v, $decimal_sep) !== false) {
            $decparts = explode($decimal_sep, $v);
            $parts[$k] = $decparts[0];
        }
    }

    $groupLengths = array_map('strlen', $parts);
    if (max($groupLengths) == 3) {
        if ($groupLengths[0] == 3 && $groupLengths[1] != 3) {
            wp_safe_redirect(add_query_arg('format_err', rawurlencode($price), $referer));
            exit;
        }
        $price = str_replace($thousand_sep, '', $price);
    } else {
        wp_safe_redirect(add_query_arg('format_err', rawurlencode($price), $referer));
        exit;
    }
}

return $price;
