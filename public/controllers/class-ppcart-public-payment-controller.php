<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Public Stripe intent and hosted checkout flow.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Creates and updates Stripe intents, setup intents, and hosted Checkout sessions.
 */
require_once __DIR__ . '/payment/traits/trait-ppcart-public-payment-connect.php';
require_once __DIR__ . '/payment/traits/trait-ppcart-public-payment-preload.php';
require_once __DIR__ . '/payment/traits/trait-ppcart-public-payment-customer.php';
require_once __DIR__ . '/payment/traits/trait-ppcart-public-payment-intent.php';
require_once __DIR__ . '/payment/traits/trait-ppcart-public-payment-setup-intent.php';
require_once __DIR__ . '/payment/traits/trait-ppcart-public-payment-update-intent.php';
class PPCart_Public_Payment_Controller
{
    use PPCart_Public_Payment_Connect_Trait;
    use PPCart_Public_Payment_Preload_Trait;
    use PPCart_Public_Payment_Customer_Trait;
    use PPCart_Public_Payment_Intent_Trait;
    use PPCart_Public_Payment_Setup_Intent_Trait;
    use PPCart_Public_Payment_Update_Intent_Trait;

    public function __construct()
    {
        add_action('init', [ $this, 'schedule_preloaded_intent_cleanup' ], 20);
        add_action('ppcart_cleanup_preloaded_intents', [ $this, 'cleanup_preloaded_intents' ]);
    }

    private function remove_hook_callbacks_by_class($hook_name, $class_name, $method_name)
    {
        global $wp_filter;

        if (empty($wp_filter[ $hook_name ]) || ! isset($wp_filter[ $hook_name ]->callbacks)) {
            return;
        }

        foreach ($wp_filter[ $hook_name ]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;

                if (! $this->hook_callback_matches_class_method($function, $class_name, $method_name)) {
                    continue;
                }

                remove_action($hook_name, $function, $priority);
            }
        }
    }

    private function hook_callback_matches_class_method($function, $class_name, $method_name)
    {
        if (! is_array($function) || ! isset($function[0], $function[1])) {
            return false;
        }

        return $method_name === $function[1] && $this->hook_callback_target_matches_class($function[0], $class_name);
    }

    private function hook_callback_target_matches_class($target, $class_name)
    {
        if (is_object($target)) {
            return is_a($target, $class_name);
        }

        return is_string($target) && is_a($target, $class_name, true);
    }

    private function clear_temp_order_meta($order_id)
    {
        ppcart_delete_post_meta($order_id, 'temp_order_token');
        ppcart_delete_post_meta($order_id, 'preloaded_intent');
        ppcart_delete_post_meta($order_id, 'preloaded_intent_created');
        ppcart_delete_post_meta($order_id, 'preloaded_intent_cleanup_attempts');
    }
}
