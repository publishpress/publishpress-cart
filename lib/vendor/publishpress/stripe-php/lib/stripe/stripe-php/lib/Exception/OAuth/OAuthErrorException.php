<?php

namespace PublishPress\Stripe\Exception\OAuth;

/**
 * Implements properties and methods common to all (non-SPL) Stripe OAuth
 * exceptions.
 */
abstract class OAuthErrorException extends \PublishPress\Stripe\Exception\ApiErrorException
{
    protected function constructErrorObject()
    {
        if (null === $this->jsonBody) {
            return null;
        }

        return \PublishPress\Stripe\OAuthErrorObject::constructFrom($this->jsonBody);
    }
}
