<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class for Stripe Service.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart_Stripe
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Stripe
{
    /**
     * The single instance of the class.
     *
     * @var PPCart_Stripe
     * @since 1.0.0
     */
    protected static $_instance = null;
    protected $stripeKeys = [];

    /**
     * @var \PublishPress\Stripe\StripeClient|false
     */
    protected $stripe = false;

    public static function instance()
    {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function stripe()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-stripe-stripe.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Update payment method to existing subscription
     */
    public function updatePaymentMethod($subscription_id, $payment_method, $customer_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-updatepaymentmethod.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethodToCustomer($payment_method, $customer_id)
    {
        if (! $this->stripe) {
            return false;
        }

        try {
            $method_response = $this->stripe->paymentMethods->attach(
                $payment_method,
                ['customer' => $customer_id]
            );

            return $method_response;
        } catch (\PublishPress\Stripe\Exception\InvalidRequestException $e) {
            ppcart_helper()->logException($e, __LINE__, __FILE__);
        }

        return false;
    }

    /**
     * Set default Payment Method for all subscriptions
     */
    public function setDefaultPaymentMethod($customer_id, $payment_method)
    {
        if (! $this->stripe) {
            return false;
        }

        try {
            $this->stripe->customers->update(
                $customer_id,
                ['invoice_settings' => ['default_payment_method' => $payment_method]]
            );

            return true;
        } catch (\PublishPress\Stripe\Exception\InvalidRequestException $e) {
            ppcart_helper()->logException($e, __LINE__, __FILE__);
        }

        return false;
    }

    public function getPaymentMethods($customer_id)
    {
        $cards = [];
        if (! $this->stripe) {
            return $cards;
        }

        try {
            $cards =  $this->stripe->customers->allPaymentMethods(
                $customer_id,
                ['type' => 'card']
            );

            return $cards;
        } catch (\Exception $e) {
            ppcart_helper()->logException($e, __LINE__, __FILE__);
        }

        return $cards;
    }
}
