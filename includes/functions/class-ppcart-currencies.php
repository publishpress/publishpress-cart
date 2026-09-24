<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Provides currency names, symbols, and decimal rules.
 */
class PPCart_Currencies
{
    /**
     * Return the supported currency labels.
     *
     * @return array
     */
    public static function get_currencies()
    {
        return include __DIR__ . '/currency-data/currencies.php';
    }


    /**
     * Return currency symbols keyed by currency code.
     *
     * @return array
     */
    public static function get_currency_symbols()
    {
        return include __DIR__ . '/currency-data/currency-symbols.php';
    }

    /**
     * Return currencies that do not use decimal minor units.
     *
     * @return array
     */
    public static function get_zero_decimal_currencies()
    {
        return include __DIR__ . '/currency-data/zero-decimal-currencies.php';
    }
}
