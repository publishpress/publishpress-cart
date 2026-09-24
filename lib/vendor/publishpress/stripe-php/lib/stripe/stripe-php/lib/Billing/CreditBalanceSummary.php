<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Billing;

/**
 * Indicates the billing credit balance for billing credits granted to a customer.
 *
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property ((object{available_balance: (object{monetary: null|(object{currency: string, value: int}&\PublishPress\Stripe\StripeObject), type: string}&\PublishPress\Stripe\StripeObject), ledger_balance: (object{monetary: null|(object{currency: string, value: int}&\PublishPress\Stripe\StripeObject), type: string}&\PublishPress\Stripe\StripeObject)}&\PublishPress\Stripe\StripeObject))[] $balances The billing credit balances. One entry per credit grant currency. If a customer only has credit grants in a single currency, then this will have a single balance entry.
 * @property string|\PublishPress\Stripe\Customer $customer The customer the balance is for.
 * @property null|string $customer_account The account the balance is for.
 * @property bool $livemode If the object exists in live mode, the value is <code>true</code>. If the object exists in test mode, the value is <code>false</code>.
 */
class CreditBalanceSummary extends \PublishPress\Stripe\SingletonApiResource
{
    const OBJECT_NAME = 'billing.credit_balance_summary';

    /**
     * Retrieves the credit balance summary for a customer.
     *
     * @param null|array|string $opts
     *
     * @return CreditBalanceSummary
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public static function retrieve($opts = null)
    {
        $opts = \PublishPress\Stripe\Util\RequestOptions::parse($opts);
        $instance = new static(null, $opts);
        $instance->refresh();

        return $instance;
    }
}
