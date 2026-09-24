<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_enabled_processors()
{
    $processors = [];
    if (get_option('_ppcart_cashondelivery_enable', '1') == '1') {
        $processors[] = __('Cash on Delivery', 'publishpress-cart');
    }
    if (get_option('_ppcart_stripe_enable') == '1') {
        $processors[] = 'Stripe';
    }
    if (get_option('_ppcart_paypal_enable') == '1') {
        $processors[] = 'PayPal';
    }
    $processors = apply_filters('ppcart_enabled_processors', $processors);
    if (! is_array($processors)) {
        $processors = [];
    }
    return implode(', ', $processors);
}

function ppcart_payment_methods()
{
    global $ppcart_stripe;
    $payment_methods = [];

    // Stripe
    if ($option_val = get_option('_ppcart_stripe_enable') == '1') {
        if (is_array($ppcart_stripe)) {
            $payment_methods['stripe'] = esc_html__('Stripe', 'publishpress-cart');
        }
    }

    // COD
    if ($option_val = get_option('_ppcart_cashondelivery_enable', '1') == '1') {
        $payment_methods['cashondelivery'] = esc_html__('Cash on Delivery', 'publishpress-cart');
    }

    $payment_methods = apply_filters('ppcart_enabled_payment_gateways', $payment_methods);
    return is_array($payment_methods) ? $payment_methods : [];
}

function ppcart_states_list($cc = null)
{
    $states = apply_filters('ppcart_states', require dirname(__DIR__) . '/ppcart-states.php');
    if (! is_array($states)) {
        $states = [];
    }
    if (! is_null($cc)) {
        return $states[ $cc ] ?? false;
    } else {
        return $states;
    }
}

function ppcart_countries_list()
{
    $countries = apply_filters('ppcart_countries', require dirname(__DIR__) . '/ppcart-countries.php');
    return is_array($countries) ? $countries : [];
}

function ppcart_currency_countries_code_list()
{
    return apply_filters('ppcart_currency_countries_code', require dirname(__DIR__) . '/ppcart-allowed-stripe-express-codes.php');
}

function ppcart_vat_countries_list()
{

    return [
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'BG' => 'Bulgaria',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DE' => 'Germany',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'GR' => 'Greece',
        'ES' => 'Spain',
        'FI' => 'Finland',
        'FR' => 'France',
        'HR' => 'Croatia',
        'GB' => 'United Kingdom',
        'HU' => 'Hungary',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'LV' => 'Latvia',
        'MT' => 'Malta',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SE' => 'Sweden',
        'SI' => 'Slovenia',
        'SK' => 'Slovakia',
    ];
}

function ppcart_states_autocomplte_format_list()
{
    $state_list = [];
    $cstates = ppcart_states_list();
    foreach ($cstates as $states) {
        foreach ($states as $iso_code => $state) {
            $state_list[] = ['label' => $state,'value' => $iso_code];
        }
    }
    return $state_list;
}

function ppcart_countries_autocomplte_format_list()
{
    $countries_list = [];
    $countries = ppcart_countries_list();
    foreach ($countries as $iso_code => $country) {
        $countries_list[] = ['label' => $country,'value' => $iso_code];
    }
    return $countries_list;
}
