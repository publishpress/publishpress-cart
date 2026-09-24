<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\Terminal;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class OnboardingLinkService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * Creates a new <code>OnboardingLink</code> object that contains a redirect_url
     * used for onboarding onto Tap to Pay on iPhone.
     *
     * @param null|array{expand?: string[], link_options: array{apple_terms_and_conditions?: array{allow_relinking?: bool, merchant_display_name: string}}, link_type: string, on_behalf_of?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\Terminal\OnboardingLink
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/terminal/onboarding_links', $params, $opts);
    }
}
