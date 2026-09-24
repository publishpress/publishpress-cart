<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Preload_Trait
{
    private function has_checkout_complete_side_effects()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-has-checkout-complete-side-effects.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function has_upsell_side_effects()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-has-upsell-side-effects.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function has_preload_order_created_side_effects()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-has-order-created-side-effects.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function has_preload_pre_click_side_effects()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-has-pre-click-side-effects.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function has_unexpected_hook_callbacks($hook_name, $allowed_callbacks = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-has-unexpected-hook-callbacks.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function is_allowed_hook_callback($function, $allowed_callbacks)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-is-allowed-hook-callback.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function can_use_direct_confirmation($ppcart_product)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-can-use-direct-confirmation.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function can_preload_payment_intent($ppcart_product)
    {
        return $this->can_use_direct_confirmation($ppcart_product) && ! $this->has_preload_pre_click_side_effects() && ! $this->has_preload_order_created_side_effects();
    }

    public function schedule_preloaded_intent_cleanup()
    {
        if (! wp_next_scheduled('ppcart_cleanup_preloaded_intents')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'ppcart_cleanup_preloaded_intents');
        }
    }

    public function cleanup_preloaded_intents()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/payment-preload-cleanup-preloaded-intents.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
