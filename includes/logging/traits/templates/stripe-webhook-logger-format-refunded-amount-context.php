<?php

if (! defined('ABSPATH')) {
    exit;
}


$currency = ! empty($context['currency']) ? strtoupper((string) $context['currency']) : '';

if (isset($context['amount_refunded_display'])) {
    return self::format_amount_value($context['amount_refunded_display'], $currency);
}

if (isset($context['amount_refunded'])) {
    $amount = function_exists('ppcart_format_stripe_number')
        ? ppcart_format_stripe_number(absint($context['amount_refunded']), $currency ? $currency : 'USD')
        : (absint($context['amount_refunded']) / 100);
    return self::format_amount_value($amount, $currency);
}

return '-';
