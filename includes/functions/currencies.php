<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/class-ppcart-currencies.php';

function ppcart_get_currencies()
{
    return PPCart_Currencies::get_currencies();
}

function ppcart_get_currency_symbols()
{
    return PPCart_Currencies::get_currency_symbols();
}

function ppcart_get_zero_decimal_currencies()
{
    return PPCart_Currencies::get_zero_decimal_currencies();
}
