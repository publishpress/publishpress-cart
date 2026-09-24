<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Hosted_Checkout_Trait
{
    /**
     * Finalize the pending order behind a completed Stripe Checkout Session. Idempotent.
     *
     * @param object|array $session Stripe Checkout Session resource.
     * @param object|null  $stripe  Stripe client.
     * @param object|null  $event   Source webhook event.
     * @return PPCart_Order|false
     */
    public static function sync_checkout_session_completed($session, $stripe = null, $event = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-hosted-checkout-sync-checkout-session-completed.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Create (or update) the local subscription record behind a completed hosted
     * Checkout Session and link it to its first order. Idempotent.
     *
     * @param PPCart_Order $order           Finalized local order.
     * @param object|array   $session         Stripe Checkout Session resource.
     * @param string         $subscription_id Stripe subscription id from the session.
     * @param object|null    $stripe          Stripe client, used to fetch the subscription.
     * @return PPCart_Subscription|false
     */
    private static function link_hosted_checkout_subscription($order, $session, $subscription_id, $stripe = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-hosted-checkout-link-hosted-checkout-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Ensure $order->plan is a plan object before it is read by from_order().
     *
     * @param PPCart_Order $order Order whose plan should be normalized in place.
     * @return void
     */
    private static function hydrate_order_plan($order)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-hosted-checkout-hydrate-order-plan.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Backfill recurring subscription fields from the authoritative Stripe subscription.
     * Only fills values that are currently empty so plan-derived data is kept.
     *
     * @param PPCart_Subscription $sub        Local subscription being linked.
     * @param object|array          $stripe_sub Retrieved Stripe subscription resource.
     * @param array                 $mapped     Result of map_subscription() for $stripe_sub.
     * @return void
     */
    private static function backfill_recurring_from_stripe_subscription($sub, $stripe_sub, $mapped)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/hosted-checkout-backfill-recurring.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Extract an id from a Stripe resource that may be an id string or object.
     *
     * @param mixed $resource Stripe resource or id string.
     * @return string
     */
    private static function get_stripe_resource_id_from($resource)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-hosted-checkout-get-stripe-resource-id-from.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
