<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\Tax;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class AssociationService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * Finds a tax association object by PaymentIntent id.
     *
     * @param null|array{expand?: string[], payment_intent: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Tax\Association
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function find($params = null, $opts = null)
    {
        return $this->request('get', '/v1/tax/associations/find', $params, $opts);
    }
}
