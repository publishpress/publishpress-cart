<?php

if (! defined('ABSPATH')) {
    exit;
}


$stripe_sub_id = self::get($stripe_sub, 'id', '');
if ('' === $stripe_sub_id) {
    return false;
}

if (! $force && ! self::subscription_belongs_to_site($stripe_sub)) {
    return false;
}

if ($stripe) {
    try {
        $stripe_sub = $stripe->subscriptions->retrieve($stripe_sub_id);
    } catch (Exception $e) {
        // Use the event resource when retrieval fails; reconciliation can correct it later.
    }
}

$local_sub = self::find_subscription_for_stripe_subscription($stripe_sub);
if (! $local_sub || ! $local_sub->id) {
    return false;
}

$mapped = self::map_subscription($stripe_sub);

return PPCart_Stripe_Sync_Context::run(
    function () use ($local_sub, $mapped, $stripe_sub, $event) {
        $local_sub->status = self::maybe_payment_plan_completed_status($local_sub, $mapped['status']);
        $local_sub->sub_status = $mapped['sub_status'];
        $local_sub->sub_next_bill_date = $mapped['sub_next_bill_date'];
        $local_sub->cancel_at = $mapped['cancel_at'];

        if ('canceled' === $local_sub->status && empty($local_sub->cancel_date)) {
            $canceled_at = self::get($stripe_sub, 'canceled_at', 0);
            $local_sub->cancel_date = $canceled_at ? gmdate('Y-m-d', (int) $canceled_at) : gmdate('Y-m-d');
        } elseif ('canceled' !== $mapped['status']) {
            // A cancellation scheduled in Stripe is a pending cancellation in Cart, the same
            // as Cart's own cancel-at-period-end action. cancel_date is the day it was requested.
            if (empty($mapped['cancel_at'])) {
                // The scheduled cancellation was removed in Stripe.
                $local_sub->cancel_date = '';
            } elseif (empty($local_sub->cancel_date) && self::is_pending_cancellation($local_sub, $mapped['cancel_at'])) {
                $canceled_at = self::get($stripe_sub, 'canceled_at', 0);
                $local_sub->cancel_date = $canceled_at ? gmdate('Y-m-d', (int) $canceled_at) : gmdate('Y-m-d');
            }
        }

        $local_sub->store();
        self::store_subscription_owned_meta($local_sub, $mapped);
        ppcart_update_post_meta($local_sub->id, 'stripe_raw_status', $mapped['raw_status']);
        self::mark_resource_event($local_sub->id, $event);
        self::mark_successful_sync($local_sub->id);

        return $local_sub;
    }
);
