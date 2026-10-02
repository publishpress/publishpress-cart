<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Public Stripe hosted Checkout Session flow.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Creates Stripe-hosted Checkout Sessions for products using hosted checkout.
 */
class PPCart_Public_Hosted_Checkout_Controller
{
    public static function get_stripe_connect_config()
    {
        return ppcart_get_stripe_connect_config();
    }

    private function add_connect_args_to_payment_intent($args, $amount_for_stripe)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/hosted-checkout-controller-add-connect-args-to-payment-intent.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function is_connect_destination_configured()
    {
        $connect = $this->get_stripe_connect_config();
        if (! $connect['enabled']) {
            return true;
        }

        if (! empty($connect['is_oauth_access_token_key'])) {
            return true;
        }

        global $ppcart_stripe;
        $mode = isset($ppcart_stripe['mode']) ? sanitize_text_field((string) $ppcart_stripe['mode']) : 'test';

        if (function_exists('ppcart_get_stripe_platform_credentials_status')) {
            $credentials_status = ppcart_get_stripe_platform_credentials_status($mode);
            if (! empty($credentials_status['is_direct'])) {
                return true;
            }
        }

        return ! empty($connect['destination']);
    }

    private function get_connect_configuration_error_message()
    {
        return __('Stripe Connect is required but not configured correctly. Please contact the site administrator.', 'publishpress-cart');
    }

    private function add_connect_args_to_subscription($args)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/hosted-checkout-controller-add-connect-args-to-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Reuse only the logged-in user's own stored customer; otherwise create a
     * new one. Never looks customers up by the posted email.
     *
     * @param object $stripe        Stripe client.
     * @param array  $customer_args Customer create params.
     * @param string $email         Posted email (not used).
     * @return object
     */
    private function get_or_create_stripe_customer($stripe, $customer_args, $email)
    {
        global $ppcart_stripe;
        unset($email);

        $mode        = $ppcart_stripe['mode'] ?? '';
        $customer_id = PPCart_Stripe_Checkout_Customer::get_live_current_user_customer_id($stripe, $mode);
        if ('' !== $customer_id) {
            return (object) ['id' => $customer_id];
        }

        return PPCart_Stripe_Checkout_Customer::create_customer($stripe, (array) $customer_args, $mode);
    }

    private function is_missing_stripe_customer_exception($exception)
    {
        if (! is_a($exception, 'PublishPress\\Stripe\\Exception\\InvalidRequestException')) {
            return false;
        }

        if (false !== stripos($exception->getMessage(), 'No such customer')) {
            return true;
        }

        $stripe_code = method_exists($exception, 'getStripeCode') ? $exception->getStripeCode() : '';
        if ('resource_missing' !== $stripe_code) {
            return false;
        }

        $stripe_param = method_exists($exception, 'getStripeParam') ? $exception->getStripeParam() : '';
        if ('customer' === $stripe_param) {
            return true;
        }

        $error = method_exists($exception, 'getError') ? $exception->getError() : null;
        $param = (is_object($error) && isset($error->param)) ? $error->param : '';

        return 'customer' === $param;
    }

    private function is_missing_stripe_resource_exception($exception)
    {
        if (is_a($exception, 'PublishPress\\Stripe\\Exception\\InvalidRequestException')) {
            $stripe_code = method_exists($exception, 'getStripeCode') ? $exception->getStripeCode() : '';
            if ('resource_missing' === $stripe_code) {
                return true;
            }
        }

        return false !== stripos($exception->getMessage(), 'No such payment_intent');
    }

    public function create_checkout_session()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/hosted-checkout-controller-create-checkout-session.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
