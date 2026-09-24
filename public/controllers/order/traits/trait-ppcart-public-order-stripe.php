<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Order_Stripe_Trait
{
    public function get_stripe_save_helper()
    {
        if (null === $this->stripe_save_helper) {
            $this->stripe_save_helper = new PPCart_Stripe_Save_Helper();
        }

        return $this->stripe_save_helper;
    }

    private function store_stripe_owned_record($record)
    {
        if (function_exists('ppcart_stripe_sync_context_run') && isset($record->pay_method) && 'stripe' === $record->pay_method) {
            return ppcart_stripe_sync_context_run(
                function () use ($record) {
                    return $record->store();
                }
            );
        }

        return $record->store();
    }

    public function update_stripe_order_status()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-stripe-update-stripe-order-status.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function clear_temp_order_meta($order_id)
    {
        ppcart_delete_post_meta($order_id, 'temp_order_token');
        ppcart_delete_post_meta($order_id, 'preloaded_intent');
        ppcart_delete_post_meta($order_id, 'preloaded_intent_created');
        ppcart_delete_post_meta($order_id, 'preloaded_intent_cleanup_attempts');
    }

    private function is_finalized_temp_order_response($temp_order_id, $response_order_id)
    {
        if (! $temp_order_id || $temp_order_id !== $response_order_id) {
            return false;
        }

        $existing_order = new PPCart_Order($temp_order_id);

        return $existing_order->id && in_array($existing_order->status, [ 'paid', 'completed' ], true);
    }

    private function sanitize_order_status_response()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified before this helper reads the nested response payload.
        if (! isset($_POST['response']) || ! is_array($_POST['response'])) {
            return [];
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parser sanitizes by field after unslash at the read site; URL fields keep their query separators.
        return ppcart_parse_stripe_status_response(wp_unslash($_POST['response']), false);
    }

    public function update_stripe_card()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-stripe-ppcart-update-stripe-card.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function show_stripe_payment_method($order)
    {
        if (!empty($order->customer_id)) {
            $instance = PPCart_Stripe::instance();

            if (! $instance->stripe()) {
                return;
            }

            $cards = $instance->getPaymentMethods($order->customer_id);
            if (!empty($cards)) {
                $order->card = current($cards['data']);
                ppcart_helper()->renderTemplate('my-account/card-details', $order);
            }
        }
    }
}
