<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\V2\Commerce\ProductCatalog;

/**
 * Service factory class for API resources in the ProductCatalog namespace.
 *
 * @property ImportService $imports
 */
class ProductCatalogServiceFactory extends \PublishPress\Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'imports' => ImportService::class,
    ];

    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}
