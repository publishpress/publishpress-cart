<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\Identity;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class VerificationReportService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * List all verification reports.
     *
     * @param null|array{client_reference_id?: string, created?: array|int, ending_before?: string, expand?: string[], limit?: int, starting_after?: string, type?: string, verification_session?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Collection<\PublishPress\Stripe\Identity\VerificationReport>
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/identity/verification_reports', $params, $opts);
    }

    /**
     * Retrieves an existing VerificationReport.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Identity\VerificationReport
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/identity/verification_reports/%s', $id), $params, $opts);
    }
}
