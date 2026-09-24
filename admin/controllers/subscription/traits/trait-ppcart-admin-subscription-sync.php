<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Subscription_Sync_Trait
{
    public function sync_stripe_subscription($order)
    {
        $sub = new PPCart_Subscription($order->id);
        if (! class_exists('PPCart_Stripe_Sync')) {
            throw new Exception(esc_html__('Stripe sync layer is unavailable.', 'publishpress-cart'));
        }

        try {
            PPCart_Stripe_Sync::sync_subscription_by_local($sub);
            return __('Subscription and invoices synced successfully.', 'publishpress-cart');
        } catch (\Exception $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Preserve failure details for manual sync troubleshooting.
            error_log("Stripe manual sync: Failed for subscription ID {$order->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function sync_stripe_order($order)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-sync-sync-stripe-order.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function sync_order_ajax()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-sync-ppcart-sync-order-ajax.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function sync_subscription_ajax()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/admin-subscription-sync-ajax.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
