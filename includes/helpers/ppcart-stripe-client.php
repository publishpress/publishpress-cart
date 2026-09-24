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
