<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * First-party Stripe client. Use this instead of StripeClient so list/search
 * methods keep working after the SDK namespace prefix.
 *
 * @param string $secret Stripe secret or restricted key.
 * @return PPCart_Stripe_Client
 */
function ppcart_stripe_client($secret)
{
    if (! class_exists('PPCart_Stripe_Client', false)) {
        require_once dirname(__DIR__) . '/class-ppcart-stripe-client.php';
    }

    return new PPCart_Stripe_Client($secret);
}

/**
 * Option that stores the id of Cart's own Stripe Customer Portal
 * configuration for the current Stripe mode (test or live).
 *
 * @return string
 */
function ppcart_stripe_portal_configuration_option()
{
    $mode = 'live' === get_option('_ppcart_stripe_api') ? 'live' : 'test';

    return "_ppcart_stripe_{$mode}_portal_configuration_id";
}
