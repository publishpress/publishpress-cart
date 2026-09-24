<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use ReflectionMethod;
use PPCart_Order;
use PPCart_Subscription;
use Tests\Support\Integration\StripeSyncTestCase;

class ChargeAndInvoiceSyncTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_001_paid_charge_sync_skips_redundant_write_for_already_synced_order(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_paid_noop',
                'status' => 'paid',
                'payment_status' => 'paid',
            )
        );
        $modifiedBefore = $this->getPostModified($orderId);

        PPCart_Stripe_Sync::sync_charge_status(
            (object) array(
                'id' => 'ch_paid_noop',
                'payment_intent' => 'pi_paid_noop',
                'amount' => 20000,
                'currency' => 'usd',
                'refunded' => false,
                'metadata' => (object) array(
                    'ppcart_product_id' => 123,
                    'ppcart_order_id' => $orderId,
                ),
            )
        );

        $this->assertSame($modifiedBefore, $this->getPostModified($orderId));
    }

    public function test_IT_002_paid_charge_sync_still_stores_when_stored_id_differs_from_charge_id(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'pi_paid_update',
                'status' => 'paid',
                'payment_status' => 'paid',
            )
        );

        PPCart_Stripe_Sync::sync_charge_status(
            (object) array(
                'id' => 'ch_paid_update',
                'payment_intent' => 'pi_paid_update',
                'amount' => 20000,
                'currency' => 'usd',
                'refunded' => false,
                'metadata' => (object) array(
                    'ppcart_product_id' => 123,
                    'ppcart_order_id' => $orderId,
                ),
            )
        );

        $this->assertSame('ch_paid_update', ppcart_get_post_meta($orderId, 'transaction_id', true));
    }

    public function test_IT_003_paid_charge_sync_migrates_payment_intent_id_to_charge_id(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'pi_paid_update',
            )
        );

        PPCart_Stripe_Sync::sync_charge_status(
            (object) array(
                'id' => 'ch_paid_update',
                'payment_intent' => 'pi_paid_update',
                'amount' => 20000,
                'currency' => 'usd',
                'refunded' => false,
                'metadata' => (object) array(
                    'ppcart_product_id' => 123,
                    'ppcart_order_id' => $orderId,
                ),
            )
        );

        $this->assertSame('ch_paid_update', ppcart_get_post_meta($orderId, 'transaction_id', true));
    }

    public function test_IT_004_paid_invoice_sync_skips_redundant_write_for_already_synced_order(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_invoice_noop',
            )
        );
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_invoice_noop',
                'status' => 'paid',
                'payment_status' => 'succeeded',
                'subscription_id' => $subscriptionId,
            )
        );
        $modifiedBefore = $this->getPostModified($orderId);

        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'subscription' => 'sub_invoice_noop',
                'charge' => 'ch_invoice_noop',
                'payment_intent' => 'pi_invoice_noop',
                'amount_paid' => 20000,
                'currency' => 'usd',
                'status' => 'paid',
                'status_transitions' => (object) array(
                    'finalized_at' => 1234567890,
                ),
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_invoice_noop',
                            'metadata' => (object) array(
                                'ppcart_product_id' => 123,
                                'origin' => 'https://example.test',
                            ),
                            'period' => (object) array(
                                'end' => 3333333333,
                            ),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId),
            'invoice.payment_succeeded'
        );

        $this->assertSame($modifiedBefore, $this->getPostModified($orderId));
    }

    public function test_IT_005_charge_sync_does_not_flip_a_fully_refunded_order_back_to_paid(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_refunded',
                'status' => 'refunded',
                'payment_status' => 'refunded',
                'refund_log' => array(),
            )
        );

        $refundedCharge = (object) array(
            'id' => 'ch_refunded',
            'amount' => 1000,
            'amount_refunded' => 1000,
            'currency' => 'usd',
            'refunded' => true,
            'metadata' => (object) array(
                'ppcart_product_id' => 123,
                'ppcart_order_id' => $orderId,
            ),
            'refunds' => (object) array(
                'data' => array(
                    (object) array(
                        'id' => 're_refunded',
                        'amount' => 1000,
                        'created' => 1234567890,
                    ),
                ),
            ),
        );

        PPCart_Stripe_Sync::sync_charge_status(
            $refundedCharge,
            null,
            $this->createStripeChargeClientStub($refundedCharge)
        );

        $this->assertSame('refunded', ppcart_get_post_meta($orderId, 'status', true));
    }

    public function test_IT_006_charge_sync_routes_refunded_charges_through_refund_log_recording(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_refunded',
                'status' => 'refunded',
                'payment_status' => 'refunded',
                'refund_log' => array(),
            )
        );

        $refundedCharge = (object) array(
            'id' => 'ch_refunded',
            'amount' => 1000,
            'amount_refunded' => 1000,
            'currency' => 'usd',
            'refunded' => true,
            'metadata' => (object) array(
                'ppcart_product_id' => 123,
                'ppcart_order_id' => $orderId,
            ),
            'refunds' => (object) array(
                'data' => array(
                    (object) array(
                        'id' => 're_refunded',
                        'amount' => 1000,
                        'created' => 1234567890,
                    ),
                ),
            ),
        );

        PPCart_Stripe_Sync::sync_charge_status(
            $refundedCharge,
            null,
            $this->createStripeChargeClientStub($refundedCharge)
        );

        $refundLog = ppcart_get_post_meta($orderId, 'refund_log', true);

        $this->assertIsArray($refundLog);
        $this->assertArrayHasKey('re_refunded', $refundLog);
    }

    public function test_IT_007_subscription_sync_clears_next_bill_date_when_subscription_is_canceled(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_clear',
                'sub_next_bill_date' => '999',
                'cancel_at' => 888,
            )
        );

        PPCart_Stripe_Sync::sync_subscription_resource(
            (object) array(
                'id' => 'sub_clear',
                'status' => 'canceled',
                'current_period_end' => 999,
                'cancel_at' => 0,
                'canceled_at' => 1234567890,
                'metadata' => (object) array(
                    'ppcart_subscription_id' => $subscriptionId,
                ),
            ),
            null,
            null,
            true
        );

        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'sub_next_bill_date', true));
    }

    public function test_IT_008_subscription_sync_deletes_cancel_at_meta_when_stripe_clears_it(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_clear',
                'sub_next_bill_date' => '999',
                'cancel_at' => 888,
            )
        );

        PPCart_Stripe_Sync::sync_subscription_resource(
            (object) array(
                'id' => 'sub_clear',
                'status' => 'canceled',
                'current_period_end' => 999,
                'cancel_at' => 0,
                'canceled_at' => 1234567890,
                'metadata' => (object) array(
                    'ppcart_subscription_id' => $subscriptionId,
                ),
            ),
            null,
            null,
            true
        );

        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'cancel_at', true));
    }

    public function test_IT_009_subscription_sync_stores_the_mapped_status_while_clearing_meta(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'subscription_id' => 'sub_clear',
            )
        );

        PPCart_Stripe_Sync::sync_subscription_resource(
            (object) array(
                'id' => 'sub_clear',
                'status' => 'canceled',
                'current_period_end' => 999,
                'cancel_at' => 0,
                'canceled_at' => 1234567890,
                'metadata' => (object) array(
                    'ppcart_subscription_id' => $subscriptionId,
                ),
            ),
            null,
            null,
            true
        );

        $this->assertSame('canceled', ppcart_get_post_meta($subscriptionId, 'status', true));
    }

    public function test_IT_010_invoice_line_selection_picks_the_subscription_product_line(): void
    {
        $method = new ReflectionMethod(PPCart_Stripe_Sync::class, 'find_invoice_product_line');
        $method->setAccessible(true);

        $invoice = (object) array(
            'lines' => (object) array(
                'data' => array(
                    (object) array(
                        'metadata' => (object) array(
                            'ppcart_product_id' => 999,
                        ),
                    ),
                    (object) array(
                        'subscription_item' => 'si_expected',
                        'metadata' => (object) array(
                            'ppcart_product_id' => 123,
                        ),
                    ),
                ),
            ),
        );

        $selectedLine = $method->invoke(null, $invoice);

        $this->assertSame('si_expected', $selectedLine->subscription_item);
    }

    public function test_IT_011_invoice_line_selection_ignores_lines_without_a_subscription_item(): void
    {
        $method = new ReflectionMethod(PPCart_Stripe_Sync::class, 'find_invoice_product_line');
        $method->setAccessible(true);

        $invoice = (object) array(
            'lines' => (object) array(
                'data' => array(
                    (object) array(
                        'metadata' => (object) array(
                            'ppcart_product_id' => 123,
                        ),
                    ),
                ),
            ),
        );

        $this->assertFalse($method->invoke(null, $invoice));
    }

    public function test_dahlia_invoice_line_updates_the_first_order(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_dahlia_invoice',
            )
        );
        $orderId = $this->createStripeOrder(
            array(
                'status' => 'pending-payment',
                'payment_status' => 'pending',
                'transaction_id' => '',
                'amount' => 0,
                'subscription_id' => $subscriptionId,
            )
        );
        ppcart_update_post_meta($subscriptionId, 'first_order', $orderId);

        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'status' => 'paid',
                'currency' => 'usd',
                'amount_paid' => 1000,
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'parent' => (object) array(
                                'subscription_item_details' => (object) array(
                                    'subscription' => 'sub_dahlia_invoice',
                                    'subscription_item' => 'si_dahlia',
                                ),
                            ),
                            'metadata' => (object) array(
                                'origin' => get_site_url(),
                                'ppcart_subscription_id' => $subscriptionId,
                            ),
                            'period' => (object) array(
                                'end' => 3333333333,
                            ),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId),
            'invoice.payment_succeeded'
        );

        $this->assertSame('paid', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertSame('succeeded', ppcart_get_post_meta($orderId, 'payment_status', true));
        $this->assertSame('10.00', ppcart_get_post_meta($orderId, 'amount', true));
    }

    public function test_IT_012_invoice_sync_does_not_restore_next_bill_date_for_paused_subscriptions(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_paused_invoice',
                'status' => 'paused',
                'sub_status' => 'active',
                'sub_next_bill_date' => '',
            )
        );

        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_paused',
                            'metadata' => (object) array(
                                'ppcart_product_id' => 123,
                                'origin' => 'https://example.test',
                            ),
                            'period' => (object) array(
                                'end' => 3333333333,
                            ),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId)
        );

        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'sub_next_bill_date', true));
    }
}
