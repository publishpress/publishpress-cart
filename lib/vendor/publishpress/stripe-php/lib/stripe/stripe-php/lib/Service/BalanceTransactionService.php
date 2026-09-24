<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class BalanceTransactionService extends AbstractService
{
    /**
     * Returns a list of transactions that have contributed to the Stripe account
     * balance (for example, charges, transfers, and so on). The transactions return in
     * sorted order, with the most recent transactions appearing first.
     *
     * The previous name of this endpoint was “Balance history,” and it used the path
     * <code>/v1/balance/history</code>.
     *
     * @param null|array{created?: array|int, currency?: string, ending_before?: string, expand?: string[], limit?: int, payout?: string, source?: string, starting_after?: string, type?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Collection<\PublishPress\Stripe\BalanceTransaction>
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/balance_transactions', $params, $opts);
    }

    /**
     * Retrieves the balance transaction with the given ID.
     *
     * Note that this endpoint previously used the path
     * <code>/v1/balance/history/:id</code>.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\BalanceTransaction
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/balance_transactions/%s', $id), $params, $opts);
    }
}
