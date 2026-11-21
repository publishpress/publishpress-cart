<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Order;
use PPCart_Stripe_Sync;
use PPCart_Subscription;
use Tests\Support\Integration\StripeSyncTestCase;

class RenewalOrderFromSubscriptionTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_425_a_renewal_for_a_subscription_whose_first_order_is_gone_keeps_the_customer(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_lost_first',
                'first_order' => 999999,
                'sub_amount' => 395,
                'product_id' => 123,
                'product_name' => 'Annual Membership',
                'email' => 'renewal@example.test',
                'customer_name' => 'Renewal Customer',
            )
        );

        $order = $this->syncPaidInvoice($subscriptionId, 'sub_lost_first', 'ch_lost_first', 39500);

        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->assertGreaterThan(0, (int) $order->id);
        $this->assertEquals($subscriptionId, ppcart_get_post_meta($order->id, 'subscription_id', true));
        $this->assertSame('renewal@example.test', ppcart_get_post_meta($order->id, 'email', true));
        $this->assertSame('Renewal Customer', ppcart_get_post_meta($order->id, 'customer_name', true));
        $this->assertEquals(123, ppcart_get_post_meta($order->id, 'product_id', true));
    }

    public function test_IT_426_a_renewal_for_a_subscription_without_a_price_option_saves_its_line_item(): void
    {
        global $wpdb;

        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_no_option',
                'first_order' => 999998,
                'sub_amount' => 39,
                'product_id' => 123,
                'product_name' => 'Monthly Membership',
            )
        );

        $order = $this->syncPaidInvoice($subscriptionId, 'sub_no_option', 'ch_no_option', 3900);

        $this->assertGreaterThan(0, (int) $order->id);
        $items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare('SELECT * FROM ' . ppcart_live_table('order_items') . ' WHERE order_id = %d', $order->id) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        );
        $this->assertCount(1, $items);
        $this->assertSame('main', $items[0]->item_type);
        $this->assertSame('', $items[0]->price_id);
    }

    public function test_IT_427_invoice_sync_links_an_existing_order_that_has_no_subscription(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_orphan',
                'email' => 'orphan@example.test',
                'customer_name' => 'Orphan Customer',
            )
        );
        $orphanId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_orphan',
                'amount' => 395,
            )
        );
        ppcart_delete_post_meta($orphanId, 'email');

        $this->syncPaidInvoice($subscriptionId, 'sub_orphan', 'ch_orphan', 39500);

        $this->assertEquals($subscriptionId, ppcart_get_post_meta($orphanId, 'subscription_id', true));
        $this->assertSame('orphan@example.test', ppcart_get_post_meta($orphanId, 'email', true));
        $this->assertSame('Orphan Customer', ppcart_get_post_meta($orphanId, 'customer_name', true));
    }

    /**
     * @return PPCart_Order|false
     */
    private function syncPaidInvoice(int $subscriptionId, string $stripeId, string $chargeId, int $amount)
    {
        return PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'subscription' => $stripeId,
                'charge' => $chargeId,
                'payment_intent' => 'pi_' . $chargeId,
                'amount_paid' => $amount,
                'currency' => 'usd',
                'status' => 'paid',
                'status_transitions' => (object) array(
                    'paid_at' => 1234567890,
                    'finalized_at' => 1234567890,
                ),
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_' . $chargeId,
                            'metadata' => (object) array(
                                'ppcart_product_id' => 123,
                            ),
                            'period' => (object) array(
                                'end' => 1900000000,
                            ),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId),
            'invoice.paid'
        );
    }
}
