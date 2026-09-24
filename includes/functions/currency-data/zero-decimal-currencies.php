<?php

if (! defined('ABSPATH')) {
    exit;
}

$zero_decimal_currency = apply_filters(
    'ppcart_zero_decimal_currency',
    [
                'BIF',
                'CLP',
                'DJF',
                'GNF',
                'JPY',
                'KMF',
                'KRW',
                'MGA',
                'PYG',
                'RWF',
                'UGX',
                'VND',
                'VUV',
                'XAF',
                'XOF',
                'XPF',
            ]
);
return $zero_decimal_currency;
