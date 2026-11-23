<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Subscriptions bought before a store moved to Cart carry leftover metadata
 * keys that only Compatibility Mode reads. Their webhook events must still
 * sync when this site stores the Stripe subscription.
 */
class LeftoverMetadataSubscriptionSyncTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @param string               $stripeId Stripe subscription ID.
     * @param array<string, mixed> $metadata Subscription metadata.
     * @param array<string, mixed> $itemMetadata Price metadata on the subscription item.
     * @return object
     */
    private function canceledStripeSubscription(string $stripeId, array $metadata, array $itemMetadata): object
    {
        return json_decode(
            (string) wp_json_encode(
                array(
                    'id' => $stripeId,
                    'object' => 'subscription',
                    'status' => 'canceled',
                    'current_period_end' => 1900000000,
                    'cancel_at' => null,
                    'cancel_at_period_end' => false,
                    'canceled_at' => 1700000000,
                    'metadata' => $metadata,
                    'items' => array(
                        'object' => 'list',
                        'data' => array(
                            array(
                                'id' => 'si_leftover',
                                'metadata' => array(),
                                'price' => array('id' => 'price_leftover', 'metadata' => $itemMetadata),
                                'plan' => array('id' => 'price_leftover', 'metadata' => $itemMetadata),
                            ),
                        ),
                    ),
                )
            )
        );
    }

    public function test_cancel_event_syncs_a_stored_subscription_with_only_leftover_metadata(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(array('subscription_id' => 'sub_leftover_meta'));

        $result = PPCart_Stripe_Sync::sync_subscription_resource(
            $this->canceledStripeSubscription(
                'sub_leftover_meta',
                array('sc_subscription_id' => (string) $subscriptionId),
                array('sc_product_id' => '123')
            )
        );

        $this->assertNotFalse($result);
        $this->assertSame('canceled', ppcart_get_post_meta($subscriptionId, 'status', true));
    }

    public function test_event_for_an_unknown_subscription_without_cart_metadata_is_skipped(): void
    {
        $this->assertFalse(
            PPCart_Stripe_Sync::sync_subscription_resource(
                $this->canceledStripeSubscription(
                    'sub_not_on_this_site',
                    array('sc_subscription_id' => '999999'),
                    array('sc_product_id' => '123')
                )
            )
        );
    }
}
