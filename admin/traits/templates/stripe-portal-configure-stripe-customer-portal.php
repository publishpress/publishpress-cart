<?php

if (! defined('ABSPATH')) {
    exit;
}


try {
    $stripe = ppcart_stripe_client($secret);

    $enabled = $enabled_flag ? 'true' : 'false';

    $features = [
        'customer_update' => ['enabled' => $enabled, 'allowed_updates' => ['email', 'address', 'shipping']],
        'payment_method_update' => ['enabled' => $enabled],
        'subscription_cancel' => [
            'enabled' => $enabled,
            'cancellation_reason' => [
                'enabled' => $enabled,
                'options' => ['too_expensive', 'switched_service', 'unused', 'other'],
            ],
        ],
    ];

    // Cart keeps its own portal configuration instead of editing the account's
    // default one. On an account linked with "Connect with Stripe", Stripe refuses
    // changes to the default configuration or to one another application created.
    $config_option = ppcart_stripe_portal_configuration_option();
    $config_id = (string) get_option($config_option, '');
    $updated = false;

    if ('' !== $config_id) {
        try {
            $stripe->billingPortal->configurations->update($config_id, ['active' => 'true', 'features' => $features]);
            $updated = true;
        } catch (\PublishPress\Stripe\Exception\InvalidRequestException $e) {
            // The saved configuration belongs to another Stripe account or was removed.
            delete_option($config_option);
        }
    }

    if (! $updated && $enabled_flag) {
        $configuration = $stripe->billingPortal->configurations->create([
            'features' => $features,
            'metadata' => ['ppcart' => '1'],
        ]);
        update_option($config_option, $configuration->id, false);
    }
} catch (\Exception $e) {
    update_option('_ppcart_stripe_customer_portal_enable', '0');
    if ($enabled_flag) {
        set_transient('ppcart_customer_portal_settings_error', [
            'message' => 'Customer Portal: ' . $e->getMessage(),
            'type' => 'error',
        ], 30);
    }
}
