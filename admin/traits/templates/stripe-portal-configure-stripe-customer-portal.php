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

    $configuration = $stripe->billingPortal->configurations->all(['is_default' => true]);

    if (!empty($configuration->data)) {
        $stripe->billingPortal->configurations->update($configuration->data[0]['id'], ['features' => $features]);
    } else {
        $stripe->billingPortal->configurations->create(['features' => $features]);
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
