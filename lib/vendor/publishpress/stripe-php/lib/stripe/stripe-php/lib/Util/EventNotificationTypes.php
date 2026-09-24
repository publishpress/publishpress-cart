<?php

namespace PublishPress\Stripe\Util;

class EventNotificationTypes
{
    const v2EventMapping = [
        // The beginning of the section generated from our OpenAPI spec
        \PublishPress\Stripe\Events\V1BillingMeterErrorReportTriggeredEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V1BillingMeterErrorReportTriggeredEventNotification::class,
        \PublishPress\Stripe\Events\V1BillingMeterNoMeterFoundEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V1BillingMeterNoMeterFoundEventNotification::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsFailedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsFailedEventNotification::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsProcessingEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsProcessingEventNotification::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededEventNotification::class,
        \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededWithErrorsEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CommerceProductCatalogImportsSucceededWithErrorsEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountClosedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountClosedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountCreatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountCreatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerCapabilityStatusUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationCustomerUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantCapabilityStatusUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationMerchantUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingConfigurationRecipientUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingDefaultsUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingFutureRequirementsUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingIdentityUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountIncludingRequirementsUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountLinkReturnedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountLinkReturnedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountPersonCreatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountPersonCreatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountPersonDeletedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountPersonDeletedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreAccountPersonUpdatedEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreAccountPersonUpdatedEventNotification::class,
        \PublishPress\Stripe\Events\V2CoreEventDestinationPingEventNotification::LOOKUP_TYPE => \PublishPress\Stripe\Events\V2CoreEventDestinationPingEventNotification::class,
        // The end of the section generated from our OpenAPI spec
    ];
}
