<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\Reporting;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class ReportTypeService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * Returns a full list of Report Types.
     *
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Collection<\PublishPress\Stripe\Reporting\ReportType>
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/reporting/report_types', $params, $opts);
    }

    /**
     * Retrieves the details of a Report Type. (Certain report types require a <a
     * href="https://stripe.com/docs/keys#test-live-modes">live-mode API key</a>.).
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Reporting\ReportType
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/reporting/report_types/%s', $id), $params, $opts);
    }
}
