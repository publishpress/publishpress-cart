<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Customer_Trait
{
    /**
     * The Stripe customer stored on the logged-in user's own account, or ''.
     *
     * The posted email is not proof of identity, so it is not used to find a
     * customer. Guests always get a new customer.
     *
     * @param string $email        Posted email (not used).
     * @param string $gateway_mode Stripe gateway mode.
     * @return string
     */
    private function get_cached_stripe_customer_id($email, $gateway_mode)
    {
        global $ppcart_stripe;
        unset($email);

        if ('' === PPCart_Stripe_Checkout_Customer::get_current_user_customer_id($gateway_mode) || empty($ppcart_stripe['sk'])) {
            return '';
        }

        return PPCart_Stripe_Checkout_Customer::get_live_current_user_customer_id(ppcart_stripe_client($ppcart_stripe['sk']), $gateway_mode);
    }

    /**
     * Create a new Stripe customer for this checkout. Never reuses a customer
     * found by email, because anyone can post any email.
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

        return PPCart_Stripe_Checkout_Customer::create_customer($stripe, (array) $customer_args, $ppcart_stripe['mode'] ?? '');
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
}
