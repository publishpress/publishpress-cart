<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Events;

/**
 * @property \PublishPress\Stripe\RelatedObject $related_object Object containing the reference to API resource relevant to the event
 */
class V2CommerceProductCatalogImportsSucceededEventNotification extends \PublishPress\Stripe\V2\Core\EventNotification
{
    const LOOKUP_TYPE = 'v2.commerce.product_catalog.imports.succeeded';
    public $related_object;

    /**
     * Retrieves the full event object from the API. Make an API request on every call.
     *
     * @return V2CommerceProductCatalogImportsSucceededEvent
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function fetchEvent()
    {
        return parent::fetchEvent();
    }

    /**
     * Retrieves the related object from the API. Make an API request on every call.
     *
     * @return \PublishPress\Stripe\V2\Commerce\ProductCatalogImport
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function fetchRelatedObject()
    {
        return parent::fetchRelatedObject();
    }
}
