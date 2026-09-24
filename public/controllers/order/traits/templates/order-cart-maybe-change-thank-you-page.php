<?php

if (! defined('ABSPATH')) {
    exit;
}


// Bump redirect (deprecated)
$ob = ppcart_get_post_meta($order_id, 'order_bumps', true);
if (!$ob || !isset($ob['main'])) {
    return $formAction;
}

$ob_id = $ob['main']['id'];

// get bump redirect settings
if ($ob_id && $override = ppcart_get_post_meta($product_id, 'ob_conf_override', true)) {
    $thank_you_page = intval(ppcart_get_post_meta($product_id, 'ob_page', true));
    if ($override) {
        if (!$thank_you_page) {
            // get bump product's thank you page
            $ppcart_product = ppcart_setup_product($ob_id);
            $thank_you_page = $ppcart_product->thanks_url;
        } else {
            $thank_you_page = get_permalink($thank_you_page);
        }
        $order_bumps = ppcart_filter_input_array(
            INPUT_POST,
            [
                'ppcart-orderbump' => [
                    'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    'flags'  => FILTER_REQUIRE_ARRAY,
                ],
            ]
        );
        if (isset($order_bumps['ppcart-orderbump']['main'])) {
            return PPCart_Order::confirmation_url($thank_you_page, $order_id);
        } else {
            return $thank_you_page;
        }
    }
}
return $formAction;
