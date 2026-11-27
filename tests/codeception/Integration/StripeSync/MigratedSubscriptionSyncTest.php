<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Stripe_Sync_Context;
use PPCart_Subscription;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Sync with Stripe on subscriptions migrated from StudioCart: the cancel date,
 * $0 orders paid with no charge, and the Stripe subscription ID that the API sends.
 */
class MigratedSubscriptionSyncTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_498_sync_replaces_a_cancel_date_stamped_at_migration_with_the_stripe_date(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_migrated_cancel',
                'status' => 'canceled',
                'sub_status' => 'canceled',
                'cancel_date' => '2026-11-19',
            )
        );

        $this->syncCanceledSubscription('sub_migrated_cancel', $subscriptionId, strtotime('2021-04-27 22:08:00 UTC'));

        $this->assertSame('canceled', ppcart_get_post_meta($subscriptionId, 'status', true));
        $this->assertSame('2021-04-27', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
    }

    public function test_IT_499_sync_keeps_a_cancel_date_within_a_day_of_the_stripe_date(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_cancel_same_day',
                'status' => 'canceled',
                'sub_status' => 'canceled',
                'cancel_date' => '2021-04-28',
            )
        );

        // Stripe counts the day in UTC. The site stored the next local day.
        $this->syncCanceledSubscription('sub_cancel_same_day', $subscriptionId, strtotime('2021-04-27 22:08:00 UTC'));

        $this->assertSame('2021-04-28', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
    }

    public function test_IT_500_sync_fills_an_empty_cancel_date_from_stripe(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_cancel_empty'));

        $this->syncCanceledSubscription('sub_cancel_empty', $subscriptionId, strtotime('2021-09-02 18:12:00 UTC'));

        $this->assertSame('2021-09-02', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
    }

    public function test_IT_501_an_order_paid_by_a_zero_amount_invoice_becomes_a_stripe_order(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_zero_migrated', 'product_id' => 123, 'product_name' => 'Membership'));
        $paidAt = strtotime('2021-04-25 14:45:00 UTC');
        // A $0 first order migrated from StudioCart: no payment method and no
        // charge, but linked to its Stripe invoice.
        $orderId = $this->createStripeOrder(
            array(
                'subscription_id' => $subscriptionId,
                'pay_method' => '',
                'transaction_id' => '',
                'amount' => '0.00',
                'stripe_invoice_id' => 'in_zero_migrated',
            )
        );
        ppcart_update_post_meta($subscriptionId, 'first_order', $orderId);

        PPCart_Stripe_Sync_Context::run_manual(
            function () use ($subscriptionId, $paidAt) {
                $this->syncZeroInvoice('in_zero_migrated', 'sub_zero_migrated', $subscriptionId, $paidAt, 'paid');
            }
        );

        $this->assertSame('stripe', ppcart_get_post_meta($orderId, 'pay_method', true));
        $this->assertSame('live', ppcart_get_post_meta($orderId, 'gateway_mode', true));
        $this->assertSame('paid', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertSame(array($orderId), $this->ordersOf($subscriptionId), 'Sync must not add another order.');
    }

    public function test_IT_502_a_zero_amount_invoice_leaves_other_payment_methods_and_unpaid_invoices_alone(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_zero_other', 'product_id' => 123, 'product_name' => 'Membership'));
        $paidAt = strtotime('2021-04-25 14:45:00 UTC');
        $paypalOrderId = $this->createStripeOrder(
            array(
                'subscription_id' => $subscriptionId,
                'pay_method' => 'paypal',
                'transaction_id' => '',
                'amount' => '0.00',
                'stripe_invoice_id' => 'in_zero_paypal',
            )
        );
        ppcart_update_post_meta($subscriptionId, 'first_order', $paypalOrderId);

        $this->syncZeroInvoice('in_zero_paypal', 'sub_zero_other', $subscriptionId, $paidAt, 'paid');
        $this->assertSame('paypal', ppcart_get_post_meta($paypalOrderId, 'pay_method', true));

        $cashSubscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_zero_open', 'product_id' => 123, 'product_name' => 'Membership'));
        $cashOrderId = $this->createStripeOrder(
            array(
                'subscription_id' => $cashSubscriptionId,
                'pay_method' => 'cod',
                'transaction_id' => '',
                'amount' => '0.00',
                'status' => 'pending-payment',
                'payment_status' => 'pending-payment',
                'stripe_invoice_id' => 'in_zero_open',
            )
        );
        ppcart_update_post_meta($cashSubscriptionId, 'first_order', $cashOrderId);

        $this->syncZeroInvoice('in_zero_open', 'sub_zero_open', $cashSubscriptionId, $paidAt, 'open');
        $this->assertSame('cod', ppcart_get_post_meta($cashOrderId, 'pay_method', true), 'An unpaid invoice is no proof of a Stripe payment.');

        foreach (array_merge($this->ordersOf($subscriptionId), $this->ordersOf($cashSubscriptionId)) as $id) {
            $this->createdPostIds[] = $id;
        }
    }

    public function test_IT_503_the_api_sends_the_stripe_id_of_a_subscription_migrated_with_a_studiocart_id(): void
    {
        $migratedId = $this->createStripeSubscription(
            array(
                'subscription_id' => '81074db9-288a-4ba0-9f52-5ce8c2f6e33b',
                'stripe_subscription_id' => 'sub_migrated_api',
                'sub_end_date' => '',
            )
        );
        $stripeId = $this->createStripeSubscription(array('subscription_id' => 'sub_regular_api', 'sub_end_date' => ''));
        $otherId = $this->createStripeSubscription(array('subscription_id' => 'I-PAYPAL123', 'pay_method' => 'paypal', 'sub_end_date' => ''));

        $this->assertSame('sub_migrated_api', ppcart_webhook_order_body($migratedId)['subscription_id']);
        $this->assertSame('sub_regular_api', ppcart_webhook_order_body($stripeId)['subscription_id']);
        $this->assertSame('I-PAYPAL123', ppcart_webhook_order_body($otherId)['subscription_id']);
    }

    private function syncCanceledSubscription(string $stripeSubscriptionId, int $subscriptionId, int $canceledAt): void
    {
        PPCart_Stripe_Sync::sync_subscription_resource(
            (object) array(
                'id' => $stripeSubscriptionId,
                'status' => 'canceled',
                'current_period_end' => $canceledAt,
                'cancel_at' => null,
                'cancel_at_period_end' => false,
                'canceled_at' => $canceledAt,
                'ended_at' => $canceledAt,
                'metadata' => (object) array(
                    'ppcart_subscription_id' => $subscriptionId,
                ),
            ),
            null,
            null,
            true
        );
    }

    private function syncZeroInvoice(string $invoiceId, string $stripeSubscriptionId, int $subscriptionId, int $paidAt, string $status): void
    {
        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'object' => 'invoice',
                'id' => $invoiceId,
                'subscription' => $stripeSubscriptionId,
                'amount_paid' => 0,
                'amount_due' => 0,
                'currency' => 'usd',
                'status' => $status,
                'livemode' => true,
                'status_transitions' => (object) array('paid_at' => 'paid' === $status ? $paidAt : null, 'finalized_at' => $paidAt),
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_' . $invoiceId,
                            'metadata' => (object) array('ppcart_product_id' => 123),
                            'period' => (object) array('end' => $paidAt + MONTH_IN_SECONDS),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId),
            'paid' === $status ? 'invoice.paid' : 'invoice.finalized'
        );
    }

    /**
     * @return array<int, int>
     */
    private function ordersOf(int $subscriptionId): array
    {
        $ids = array_map(
            'intval',
            get_posts(
                array(
                    'post_type' => ppcart_query_post_types('order'),
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_key' => ppcart_meta_key('subscription_id'),
                    'meta_value' => $subscriptionId,
                )
            )
        );
        sort($ids);

        return $ids;
    }
}
