<?php

namespace PublishPress\Stripe\Util;

class EventTypes
{
    const v2EventMapping = [
        // The beginning of the section generated from our OpenAPI spec
        \PublishPress\Stripe\Events\V1BillingMeterErrorReportTriggeredEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V1BillingMeterErrorReportTriggeredEvent::class,
        \PublishPress\Stripe\Events\V1BillingMeterNoMeterFoundEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V1BillingMeterNoMeterFoundEvent::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsFailedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsFailedEvent::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsProcessingEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsProcessingEvent::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededEvent::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededWithErrorsEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededWithErrorsEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountClosedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountClosedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountCreatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountCreatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountLinkReturnedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountLinkReturnedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountPersonCreatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountPersonCreatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountPersonDeletedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountPersonDeletedEvent::class,
        \PublishPress\Stripe\Events\V2CoreAccountPersonUpdatedEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountPersonUpdatedEvent::class,
        \PublishPress\Stripe\Events\V2CoreEventDestinationPingEvent::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreEventDestinationPingEvent::class,
        // The end of the section generated from our OpenAPI spec
    ];
}
