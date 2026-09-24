<?php

if (! defined('ABSPATH')) {
    exit;
}


if (!get_option('_ppcart_stripe_settings_changed')) {
    return;
}

delete_option('_ppcart_stripe_settings_changed');
delete_transient('ppcart_express_payment_settings_error');
delete_transient('ppcart_customer_portal_settings_error');

$ppcart_mode = get_option('_ppcart_stripe_api');
$secret = ppcart_get_sensitive_option("_ppcart_stripe_{$ppcart_mode}_sk");
$is_ppcart_stripe_express_payment_enable = get_option('_ppcart_stripe_express_payment_enable');
$is_ppcart_stripe_customer_portal_enable = get_option('_ppcart_stripe_customer_portal_enable');

if (get_option('_ppcart_stripe_enable') !== '1' || empty($secret)) {
    $options = [
        '_ppcart_stripe_express_payment_enable' => 'ppcart_express_payment_settings_error',
        '_ppcart_stripe_customer_portal_enable' => 'ppcart_customer_portal_settings_error',
    ];
    foreach ($options as $key => $transient) {
        if (get_option($key)) {
            set_transient($transient, [
                'message' => (strpos($transient, 'express') !== false ? 'Express Payment' : 'Customer Portal') . ': No API key provided',
                'type' => 'error',
            ], 30);
            update_option($key, '0');
        }
    }
    return;
}


$site_domain = wp_parse_url(home_url(), PHP_URL_HOST);
$enabled = $is_ppcart_stripe_express_payment_enable ? 'true' : 'false';
$paymentMethod = ($enabled === 'true') ? 'on' : 'off';
// Express Payment Domain Registration
try {
    $stripe = ppcart_stripe_client($secret);

    $paymentMethodDomain = $stripe->request('POST', '/v1/payment_method_domains', [
        'domain_name' => $site_domain,
        'enabled' =>  $enabled,
     ], []);

    if (empty($paymentMethodDomain['id'])) {
        update_option('_ppcart_stripe_express_payment_enable', '0');
        set_transient('ppcart_express_payment_settings_error', [
            'message' => 'Express Payment: Failed to create payment method domain.',
            'type' => 'error',
        ], 30);
        return;
    }

    $paymentMethodConfigurations = $stripe->request('GET', '/v1/payment_method_configurations', [], []);
    foreach ($paymentMethodConfigurations->data as $config) {
        if ($config['name'] === 'Default' && $config['active'] && !$config['parent']) {
            $stripe->request(
                'POST',
                "/v1/payment_method_configurations/{$config['id']}",
                [
                'google_pay' => ['display_preference' => ['preference' => $paymentMethod]],
                'apple_pay'  => ['display_preference' => ['preference' => $paymentMethod]],
                'link'       => ['display_preference' => ['preference' => $paymentMethod]],
                ],
                []
            );
            // Verify Apple Pay Domain
            $this->verify_apple_pay_domain($site_domain, $paymentMethodDomain['id'], $stripe);
            break;
        }
    }
} catch (\Exception $e) {
    update_option('_ppcart_stripe_express_payment_enable', '0');
    if ($is_ppcart_stripe_express_payment_enable) {
        set_transient('ppcart_express_payment_settings_error', [
            'message' => 'Express Payment: ' . $e->getMessage(),
            'type' => 'error',
        ], 30);
    }
}

// Customer Portal Configuration
$this->configure_stripe_customer_portal($secret, $is_ppcart_stripe_customer_portal_enable);
