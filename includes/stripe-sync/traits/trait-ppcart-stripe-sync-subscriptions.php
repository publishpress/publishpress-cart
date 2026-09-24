<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Subscriptions_Trait
{
    /**
     * Sync one local subscription from its Stripe subscription ID.
     *
     * @param PPCart_Subscription|int $sub           Local subscription or ID.
     * @param bool                 $sync_invoices Whether to sync historical invoices too.
     * @return PPCart_Subscription
     * @throws Exception When the local or Stripe subscription cannot be used.
     */
    public static function sync_subscription_by_local($sub, $sync_invoices = true)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-sync-subscription-by-local.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Project a Stripe subscription resource onto the local subscription.
     *
     * @param object|array $stripe_sub Stripe subscription resource.
     * @param object|null  $stripe     Stripe client. Used to refetch fresh state.
     * @param object|null  $event      Source webhook event, when available.
     * @param bool         $force      Whether to bypass site ownership checks.
     * @return PPCart_Subscription|false
     */
    public static function sync_subscription_resource($stripe_sub, $stripe = null, $event = null, $force = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-sync-subscription-resource.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Map Stripe subscription state into local status fields.
     *
     * @param object|array $subscription Stripe subscription resource.
     * @return array Local status projection.
     */
    public static function map_subscription($subscription)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-map-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Resume a paused Stripe-backed subscription through Stripe first.
     *
     * @param PPCart_Subscription|int $sub Local subscription or ID.
     * @return PPCart_Subscription|false
     * @throws Exception When Stripe rejects the resume operation.
     */
    public static function resume_subscription($sub)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-resume-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Sync past invoices and next billing information for a subscription.
     *
     * @param PPCart_Subscription $sub    Local subscription.
     * @param object|null      $stripe Stripe client.
     * @return bool
     */
    public static function sync_subscription_invoices($sub, $stripe = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-sync-subscription-invoices.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Store subscription-owned meta and delete empty terminal values.
     *
     * @param PPCart_Subscription $sub    Local subscription.
     * @param array            $mapped Mapped Stripe status fields.
     * @return void
     */
    private static function store_subscription_owned_meta($sub, $mapped)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-store-subscription-owned-meta.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Find the local subscription linked to a Stripe subscription.
     *
     * @param object|array $stripe_sub Stripe subscription resource.
     * @return PPCart_Subscription|false
     */
    private static function find_subscription_for_stripe_subscription($stripe_sub)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-find-subscription-for-stripe-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Preserve the legacy completed status for finished payment plans.
     *
     * @param PPCart_Subscription $sub           Local subscription.
     * @param string           $mapped_status Local mapped status.
     * @return string
     */
    private static function maybe_payment_plan_completed_status($sub, $mapped_status)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-maybe-payment-plan-completed-status.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Check whether a subscription belongs to this site.
     *
     * @param object|array $subscription Stripe subscription resource.
     * @return bool
     */
    private static function subscription_belongs_to_site($subscription)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-subscriptions-subscription-belongs-to-site.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
