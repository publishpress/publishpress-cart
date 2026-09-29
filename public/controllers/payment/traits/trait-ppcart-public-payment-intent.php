<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Intent_Trait
{
    public function create_payment_intent()
    {
        global $ppcart_debug_logger;

        // Verify the checkout nonce in the AJAX handler itself, before the
        // template reads any other request field. Guests use this endpoint, so
        // there is no capability to check; the nonce is the CSRF defense.
        $nonce = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nonce = is_string($nonce) ? sanitize_text_field($nonce) : '';

        if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
            if (is_object($ppcart_debug_logger)) {
                $ppcart_debug_logger->log_event(
                    'checkout.security.failed',
                    'Checkout security check failed before creating Stripe PaymentIntent.',
                    [
                        'check' => 'ppcart_purchase_nonce:1',
                    ],
                    4
                );
            }
            wp_send_json_error(['error' => __('Invalid Request', 'publishpress-cart')]);
        }

        $__ppcart_template_result = include __DIR__ . '/templates/payment-intent-create-payment-intent.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
