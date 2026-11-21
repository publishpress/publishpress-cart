<?php

if (! defined('ABSPATH')) {
    exit;
}


if (is_numeric($sub)) {
    $sub = new PPCart_Subscription($sub);
}

$stripe_subscription_id = ($sub && $sub->id) ? $sub->get_stripe_subscription_id() : '';
if (! $stripe_subscription_id) {
    throw new Exception(esc_html__('Invalid subscription.', 'publishpress-cart'));
}

$stripe = self::get_client_for_mode($sub->gateway_mode);
$stripe_sub = $stripe->subscriptions->retrieve($stripe_subscription_id);
$sub = self::sync_subscription_resource($stripe_sub, $stripe, null, true);

if (! $sub) {
    throw new Exception(esc_html__('Stripe subscription is not linked to a PublishPress Cart subscription.', 'publishpress-cart'));
}

if ($sync_invoices) {
    self::sync_subscription_invoices($sub, $stripe);
}

return $sub;
