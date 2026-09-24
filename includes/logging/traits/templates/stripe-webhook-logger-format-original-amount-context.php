<?php

if (! defined('ABSPATH')) {
    exit;
}


$currency = ! empty($context['currency']) ? strtoupper((string) $context['currency']) : '';

if (isset($context['amount_display'])) {
    return self::format_amount_value($context['amount_display'], $currency);
}

if (isset($context['amount'])) {
    $amount = $context['amount'];
    if (isset($context['amount_refunded']) && is_numeric($amount) && (float) $amount >= 1000) {
        $amount = function_exists('ppcart_format_stripe_number')
            ? ppcart_format_stripe_number(absint($amount), $currency ? $currency : 'USD')
            : (absint($amount) / 100);
    }

    return self::format_amount_value($amount, $currency);
}

return '-';
