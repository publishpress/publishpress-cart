<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\V2;

/**
 * Service factory class for API resources in the V2 namespace.
 *
 * @property Billing\BillingServiceFactory $billing
 * @property Commerce\CommerceServiceFactory $commerce
 * @property Core\CoreServiceFactory $core
 */
class V2ServiceFactory extends \PublishPress\Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'billing' => Billing\BillingServiceFactory::class,
        'commerce' => Commerce\CommerceServiceFactory::class,
        'core' => Core\CoreServiceFactory::class,
    ];

    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}
