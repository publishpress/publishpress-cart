<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\Issuing;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class PhysicalBundleService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * Returns a list of physical bundle objects. The objects are sorted in descending
     * order by creation date, with the most recently created object appearing first.
     *
     * @param null|array{ending_before?: string, expand?: string[], limit?: int, starting_after?: string, status?: string, type?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Collection<\PublishPress\Stripe\Issuing\PhysicalBundle>
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/issuing/physical_bundles', $params, $opts);
    }

    /**
     * Retrieves a physical bundle object.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Issuing\PhysicalBundle
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/issuing/physical_bundles/%s', $id), $params, $opts);
    }
}
