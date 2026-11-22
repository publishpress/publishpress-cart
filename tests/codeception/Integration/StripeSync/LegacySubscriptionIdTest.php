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

    public function test_IT_433_sync_reuses_an_old_order_without_a_charge_id_for_the_same_payment(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_legacy_orders'));
        $orderId = $this->createStripeOrder(
            array(
                'subscription_id' => $subscriptionId,
                'transaction_id' => '',
                'amount' => '39.00',
            )
        );
        wp_update_post(
            array(
                'ID' => $orderId,
                'post_date' => '2021-07-19 17:54:18',
                'post_date_gmt' => '2021-07-19 17:54:18',
            )
        );
        ppcart_update_post_meta($subscriptionId, 'first_order', $orderId);
        $paidAt = strtotime('2021-07-19 17:54:06 UTC');

        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'subscription' => 'sub_legacy_orders',
                'charge' => 'ch_legacy_orders',
                'amount_paid' => 3900,
                'currency' => 'usd',
                'status' => 'paid',
                'status_transitions' => (object) array('paid_at' => $paidAt, 'finalized_at' => $paidAt),
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_legacy_orders',
                            'metadata' => (object) array('ppcart_product_id' => 123),
                            'period' => (object) array('end' => $paidAt + MONTH_IN_SECONDS),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId),
            'invoice.paid'
        );

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

        $this->assertSame(array($orderId), array_map('intval', $orders));
        $this->assertSame('ch_legacy_orders', ppcart_get_post_meta($orderId, 'transaction_id', true));
    }
}
