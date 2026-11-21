<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Subscription;
use PublishPress\Stripe\ApiRequestor;
use Tests\Support\Integration\StripeSyncTestCase;

require_once __DIR__ . '/InvoicePagingTest.php';

class LegacySubscriptionIdTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        update_option('_ppcart_stripe_test_sk', 'sk_test_legacy_subscription_id');
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        delete_option('_ppcart_stripe_test_sk');
        parent::tearDown();
    }

    public function test_IT_428_a_legacy_studiocart_subscription_uses_its_stripe_subscription_id(): void
    {
        $legacyId = $this->createStripeSubscription(
            array(
                'subscription_id' => '61981d21edda0',
                'stripe_subscription_id' => 'sub_legacy_helper',
            )
        );
        $currentId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_current_helper',
                'stripe_subscription_id' => 'sub_other_helper',
            )
        );

        $this->assertSame('sub_legacy_helper', (new PPCart_Subscription($legacyId))->get_stripe_subscription_id());
        $this->assertSame('sub_current_helper', (new PPCart_Subscription($currentId))->get_stripe_subscription_id());
    }

    public function test_IT_429_sync_with_stripe_works_for_a_legacy_studiocart_subscription(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => '61981d21edda0',
                'stripe_subscription_id' => 'sub_legacy_sync',
                'gateway_mode' => 'test',
            )
        );
        ApiRequestor::setHttpClient(new InvoicePagingFakeStripe('sub_legacy_sync', $subscriptionId, 2));

        PPCart_Stripe_Sync::sync_subscription_by_local(new PPCart_Subscription($subscriptionId), true);

        $orders = get_posts(
            array(
                'post_type' => ppcart_query_post_types('order'),
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'meta_query' => array(
                    array(
                        'key' => ppcart_meta_key('subscription_id'),
                        'value' => $subscriptionId,
                    ),
                ),
            )
        );

        $this->assertCount(3, $orders);
    }
}
