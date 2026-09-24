<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class InboundTransferService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * Returns a list of InboundTransfers sent from the specified FinancialAccount.
     *
     * @param null|array{ending_before?: string, expand?: string[], financial_account: string, limit?: int, starting_after?: string, status?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Collection<\PublishPress\Stripe\Treasury\InboundTransfer>
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/treasury/inbound_transfers', $params, $opts);
    }

    /**
     * Cancels an InboundTransfer.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Treasury\InboundTransfer
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function cancel($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/treasury/inbound_transfers/%s/cancel', $id), $params, $opts);
    }

    /**
     * Creates an InboundTransfer.
     *
     * @param null|array{amount: int, currency: string, description?: string, expand?: string[], financial_account: string, metadata?: array<string, string>, origin_payment_method: string, statement_descriptor?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Treasury\InboundTransfer
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/treasury/inbound_transfers', $params, $opts);
    }

    /**
     * Retrieves the details of an existing InboundTransfer.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Treasury\InboundTransfer
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/treasury/inbound_transfers/%s', $id), $params, $opts);
    }
}
