<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Subscription;
use Tests\Support\Integration\StripeSyncTestCase;

class PendingCancellationSyncTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_416_a_cancellation_scheduled_in_stripe_marks_the_subscription_pending_cancellation(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_pending',
                'sub_next_bill_date' => '1900000000',
            )
        );

        $this->syncStripeSubscription($subscriptionId, 'sub_pending', 'active', 1900000000, 1700000000);

        $this->assertSame('active', ppcart_get_post_meta($subscriptionId, 'status', true));
        $this->assertSame('2023-11-14', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
        $this->assertEquals(1900000000, ppcart_get_post_meta($subscriptionId, 'cancel_at', true));
        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'sub_next_bill_date', true));
    }

    public function test_IT_417_removing_the_scheduled_cancellation_in_stripe_clears_pending_cancellation(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_uncancel',
                'sub_next_bill_date' => '',
                'cancel_at' => 1900000000,
                'cancel_date' => '2023-11-14',
            )
        );

        $this->syncStripeSubscription($subscriptionId, 'sub_uncancel', 'active', 0, 0);

        $this->assertSame('active', ppcart_get_post_meta($subscriptionId, 'status', true));
        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'cancel_at', true));
        $this->assertEquals(1900000000, ppcart_get_post_meta($subscriptionId, 'sub_next_bill_date', true));
    }

    public function test_IT_418_a_cancellation_requested_in_cart_keeps_its_cancel_date_on_sync(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_cart_cancel',
                'cancel_date' => '2023-11-10',
            )
        );

        $this->syncStripeSubscription($subscriptionId, 'sub_cart_cancel', 'active', 1900000000, 1700000000);

        $this->assertSame('2023-11-10', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
    }

    public function test_IT_419_a_payment_plan_end_date_in_stripe_is_not_a_pending_cancellation(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_plan',
                'sub_installments' => 3,
                'sub_end_date' => '2030-03-17',
            )
        );

        $this->syncStripeSubscription($subscriptionId, 'sub_plan', 'active', strtotime('2030-03-17 08:00:00 UTC'), 0);

        $this->assertSame('active', ppcart_get_post_meta($subscriptionId, 'status', true));
        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
    }

    public function test_IT_421_cancel_at_period_end_without_cancel_at_marks_the_subscription_pending_cancellation(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_period_end',
                'sub_next_bill_date' => '1900000000',
            )
        );

        PPCart_Stripe_Sync::sync_subscription_resource(
            (object) array(
                'id' => 'sub_period_end',
                'status' => 'active',
                'current_period_end' => 1900000000,
                'cancel_at' => null,
                'cancel_at_period_end' => true,
                'canceled_at' => 1700000000,
                'metadata' => (object) array(
                    'ppcart_subscription_id' => $subscriptionId,
                ),
            ),
            null,
            null,
            true
        );

        $this->assertSame('active', ppcart_get_post_meta($subscriptionId, 'status', true));
        $this->assertSame('2023-11-14', ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
        $this->assertEquals(1900000000, ppcart_get_post_meta($subscriptionId, 'cancel_at', true));
    }

    public function test_IT_423_invoice_sync_does_not_restore_the_next_payment_of_a_pending_cancellation(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_pending_invoice',
                'sub_next_bill_date' => '',
                'cancel_at' => 1900000000,
                'cancel_date' => '2023-11-14',
            )
        );

        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'subscription' => 'sub_pending_invoice',
                'charge' => 'ch_pending_invoice',
                'payment_intent' => 'pi_pending_invoice',
                'amount_paid' => 20000,
                'currency' => 'usd',
                'status' => 'paid',
                'status_transitions' => (object) array(
                    'paid_at' => 1234567890,
                    'finalized_at' => 1234567890,
                ),
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_pending_invoice',
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

        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'sub_next_bill_date', true));
    }

    private function syncStripeSubscription(int $subscriptionId, string $stripeId, string $status, int $cancelAt, int $canceledAt): void
    {
        PPCart_Stripe_Sync::sync_subscription_resource(
            (object) array(
                'id' => $stripeId,
                'status' => $status,
                'current_period_end' => 1900000000,
                'cancel_at' => $cancelAt,
                'cancel_at_period_end' => $cancelAt > 0,
                'canceled_at' => $canceledAt ? $canceledAt : null,
                'metadata' => (object) array(
                    'ppcart_subscription_id' => $subscriptionId,
                ),
            ),
            null,
            null,
            true
        );
    }
}
