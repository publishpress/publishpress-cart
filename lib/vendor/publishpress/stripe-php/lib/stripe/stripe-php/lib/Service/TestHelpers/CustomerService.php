<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\TestHelpers;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class CustomerService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * Create an incoming testmode bank transfer.
     *
     * @param string $id
     * @param null|array{amount: int, currency: string, expand?: string[], reference?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\CustomerCashBalanceTransaction
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function fundCashBalance($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/customers/%s/fund_cash_balance', $id), $params, $opts);
    }
}
