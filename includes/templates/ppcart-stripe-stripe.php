<?php

if (! defined('ABSPATH')) {
    exit;
}


$credentials_status = function_exists('ppcart_get_stripe_platform_credentials_status') ? ppcart_get_stripe_platform_credentials_status() : false;

if (is_array($credentials_status) && ! empty($credentials_status['credentials'])) {
    $credentials = $credentials_status['credentials'];

    $this->stripeKeys['mode']    = isset($credentials['mode']) ? sanitize_key($credentials['mode']) : '';
    $this->stripeKeys['sk']      = isset($credentials['sk']) ? sanitize_text_field((string) $credentials['sk']) : '';
    $this->stripeKeys['pk']      = isset($credentials['pk']) ? sanitize_text_field((string) $credentials['pk']) : '';

    if (empty($this->stripeKeys['mode']) || empty($this->stripeKeys['sk']) || empty($credentials_status['is_usable'])) {
        $this->stripe = false;

        return false;
    }

    $this->stripeKeys['hook_id'] = get_option('_ppcart_stripe_' . $this->stripeKeys['mode'] . '_webhook_id');
} else {
    $this->stripeKeys['mode']    = sanitize_key(get_option('_ppcart_stripe_api'));
    $this->stripeKeys['sk']      = sanitize_text_field((string) ppcart_get_sensitive_option('_ppcart_stripe_' . $this->stripeKeys['mode'] . '_sk'));
    $this->stripeKeys['pk']      = sanitize_text_field((string) get_option('_ppcart_stripe_' . $this->stripeKeys['mode'] . '_pk'));

    if (empty($this->stripeKeys['mode']) || empty($this->stripeKeys['sk'])) {
        $this->stripe = false;

        return false;
    }

    $this->stripeKeys['hook_id'] = get_option('_ppcart_stripe_' . $this->stripeKeys['mode'] . '_webhook_id');
}

// Create Stripe Instance
if (! class_exists('\\PublishPress\\Stripe\\StripeClient')) {
    $this->stripe = false;

    return false;
}

try {
    $this->stripe = ppcart_stripe_client($this->stripeKeys['sk']);
} catch (\Exception $e) {
    $this->stripe = false;

    ppcart_helper()->logException($e, __LINE__, __FILE__);
}

return $this->stripe;
