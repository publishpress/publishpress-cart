<?php

if (! defined('ABSPATH')) {
    exit;
}


if (is_numeric($sub)) {
    $sub = new PPCart_Subscription($sub);
}

if (! $sub || ! $sub->id || 'stripe' !== $sub->pay_method) {
    return false;
}

$stripe = self::get_client_for_mode($sub->gateway_mode);
$stripe_sub = $stripe->subscriptions->retrieve($sub->subscription_id);
if ('canceled' === self::get($stripe_sub, 'status', '')) {
    throw new Exception(esc_html__('Canceled Stripe subscriptions cannot be resumed locally.', 'publishpress-cart'));
}

$stripe_sub = $stripe->subscriptions->update(
    $sub->subscription_id,
    [
        'pause_collection' => '',
    ]
);

return self::sync_subscription_resource($stripe_sub, null, null, true);
