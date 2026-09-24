<?php

if (! defined('ABSPATH')) {
    exit;
}


$sub = PPCart_Subscription::get_by_sub_id($subscription_id);
if (! $sub || ! $sub->id) {
    // Rehydrate the plan object first; from_order() reads $order->plan for recurring fields.
    self::hydrate_order_plan($order);
    $sub = PPCart_Subscription::from_order($order);
}

if (! $sub) {
    return false;
}

// Retrieve the Stripe subscription so status/dates match the Card Element flow.
$stripe_sub = null;
if ($stripe) {
    try {
        $stripe_sub = $stripe->subscriptions->retrieve($subscription_id);
    } catch (Exception $e) {
        $stripe_sub = null;
    }
}

$sub->subscription_id = $subscription_id;
$sub->customer_id = self::get_stripe_resource_id_from(self::get($session, 'customer', $order->customer_id));
$sub->pay_method = 'stripe';
$sub->gateway_mode = $order->gateway_mode;

$mapped = null;
if ($stripe_sub) {
    $mapped = self::map_subscription($stripe_sub);
    $sub->status = self::maybe_payment_plan_completed_status($sub, $mapped['status']);
    $sub->sub_status = $mapped['sub_status'];
    $sub->sub_next_bill_date = $mapped['sub_next_bill_date'];
    $sub->cancel_at = $mapped['cancel_at'];

    // Backfill recurring fields from Stripe when plan-derived values are missing.
    self::backfill_recurring_from_stripe_subscription($sub, $stripe_sub, $mapped);

    // Sync next bill date so store_subscription_owned_meta() doesn't delete it below.
    $mapped['sub_next_bill_date'] = $sub->sub_next_bill_date;

    // Card Element normalization: a trial with no configured trial days is active.
    if ('trialing' === $sub->status && empty($sub->free_trial_days)) {
        $sub->status = 'active';
        $sub->sub_status = 'active';
    }
} else {
    $sub->status = 'active';
    $sub->sub_status = 'active';
}

$sub->store();

if (! $sub->id) {
    return false;
}

// Persist Stripe-owned meta so later subscription/invoice webhooks stay consistent.
if ($mapped) {
    self::store_subscription_owned_meta($sub, $mapped);
    ppcart_update_post_meta($sub->id, 'stripe_raw_status', $mapped['raw_status']);
}

$order->subscription_id = $sub->id;
$order->store();

return $sub;
