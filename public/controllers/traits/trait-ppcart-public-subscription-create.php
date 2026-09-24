<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Subscription_Create_Trait
{
    public function create_subscription()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-create-create-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function create_stripe_subscription($order, $sub)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-create-create-stripe-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update_subscription_db($post_id, $subscription)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-create-update-subscription-db.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
