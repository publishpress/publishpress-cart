<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Subscription;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * A $0 invoice (paid by a coupon or the customer's credit balance) has no
 * charge or PaymentIntent. Syncing it again must not add another order.
 */
class ZeroAmountInvoiceSyncTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_438_syncing_a_zero_amount_invoice_twice_keeps_one_order(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(array('subscription_id' => 'sub_zero_invoice', 'product_id' => 123, 'product_name' => 'Membership'));
        $paidAt = strtotime('2024-09-18 10:00:00 UTC');

        $this->syncZeroInvoice('in_zero_1', 'sub_zero_invoice', $subscriptionId, $paidAt);
        $afterFirst = $this->ordersOf($subscriptionId);
        $this->syncZeroInvoice('in_zero_1', 'sub_zero_invoice', $subscriptionId, $paidAt);
        $afterSecond = $this->ordersOf($subscriptionId);

        $this->assertCount(2, $afterFirst, 'The first sync adds one order next to the first order.');
        $this->assertSame($afterFirst, $afterSecond, 'The second sync must not add another order.');

        $zeroOrder = max($afterFirst);
        $this->createdPostIds[] = $zeroOrder;
        $this->assertSame('in_zero_1', ppcart_get_post_meta($zeroOrder, 'stripe_invoice_id', true));
        $this->assertSame('paid', ppcart_get_post_meta($zeroOrder, 'status', true));
    }

    public function test_IT_439_sync_reuses_a_zero_amount_order_from_an_earlier_sync_and_keeps_two_invoices_apart(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_zero_earlier', 'product_id' => 123, 'product_name' => 'Membership'));
        $paidAt = strtotime('2024-02-01 03:19:17 UTC');
        // An order that an earlier sync made for a $0 invoice: no charge ID and
        // no invoice ID stored.
        $earlierOrderId = $this->createStripeOrder(
            array(
                'subscription_id' => $subscriptionId,
                'transaction_id' => '',
                'amount' => '0.00',
                'status' => 'paid',
                'payment_status' => 'paid',
            )
        );
        wp_update_post(
            array(
                'ID' => $earlierOrderId,
                'post_date' => gmdate('Y-m-d H:i:s', $paidAt),
                'post_date_gmt' => gmdate('Y-m-d H:i:s', $paidAt),
            )
        );
        ppcart_update_post_meta($subscriptionId, 'first_order', $earlierOrderId);

        $this->syncZeroInvoice('in_zero_earlier', 'sub_zero_earlier', $subscriptionId, $paidAt);
        $this->assertSame(array($earlierOrderId), $this->ordersOf($subscriptionId));
        $this->assertSame('in_zero_earlier', ppcart_get_post_meta($earlierOrderId, 'stripe_invoice_id', true));

        // A second $0 invoice a few hours later is its own payment.
        $this->syncZeroInvoice('in_zero_later', 'sub_zero_earlier', $subscriptionId, $paidAt + 6 * HOUR_IN_SECONDS);
        $orders = $this->ordersOf($subscriptionId);
        foreach ($orders as $orderId) {
            $this->createdPostIds[] = $orderId;
        }
        $this->assertCount(2, $orders);
        $this->assertSame('in_zero_earlier', ppcart_get_post_meta($earlierOrderId, 'stripe_invoice_id', true));
    }

    public function test_IT_441_a_charged_invoice_after_a_zero_amount_invoice_gets_its_own_order(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_zero_then_paid', 'product_id' => 123, 'product_name' => 'Membership'));
        $paidAt = strtotime('2024-09-18 10:00:00 UTC');
        $firstOrderId = $this->createStripeOrder(
            array(
                'subscription_id' => $subscriptionId,
                'transaction_id' => '',
                'amount' => '0.00',
            )
        );
        wp_update_post(array('ID' => $firstOrderId, 'post_date_gmt' => gmdate('Y-m-d H:i:s', $paidAt), 'post_date' => gmdate('Y-m-d H:i:s', $paidAt)));
        ppcart_update_post_meta($subscriptionId, 'first_order', $firstOrderId);

        $this->syncZeroInvoice('in_trial', 'sub_zero_then_paid', $subscriptionId, $paidAt);
        $this->assertSame('in_trial', ppcart_get_post_meta($firstOrderId, 'stripe_invoice_id', true));

        $renewalAt = $paidAt + MONTH_IN_SECONDS;
        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'id' => 'in_first_charge',
                'subscription' => 'sub_zero_then_paid',
                'charge' => 'ch_first_charge',
                'amount_paid' => 3900,
                'currency' => 'usd',
                'status' => 'paid',
                'status_transitions' => (object) array('paid_at' => $renewalAt, 'finalized_at' => $renewalAt),
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_first_charge',
                            'metadata' => (object) array('ppcart_product_id' => 123),
                            'period' => (object) array('end' => $renewalAt + MONTH_IN_SECONDS),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId),
            'invoice.paid'
        );

        $orders = $this->ordersOf($subscriptionId);
        foreach ($orders as $orderId) {
            $this->createdPostIds[] = $orderId;
        }
        $this->assertCount(2, $orders, 'The charged invoice is its own order, not the $0 trial order.');
        $this->assertSame('', (string) ppcart_get_post_meta($firstOrderId, 'transaction_id', true));
        $this->assertSame('in_trial', ppcart_get_post_meta($firstOrderId, 'stripe_invoice_id', true));
    }

    public function test_IT_442_an_unpaid_invoice_with_no_payment_id_leaves_a_paid_zero_amount_order_alone(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_zero_uncollectible', 'product_id' => 123, 'product_name' => 'Membership'));
        $paidAt = strtotime('2024-09-18 10:00:00 UTC');
        $zeroOrderId = $this->createStripeOrder(array('subscription_id' => $subscriptionId, 'transaction_id' => '', 'amount' => '0.00'));
        wp_update_post(array('ID' => $zeroOrderId, 'post_date_gmt' => gmdate('Y-m-d H:i:s', $paidAt), 'post_date' => gmdate('Y-m-d H:i:s', $paidAt)));
        ppcart_update_post_meta($subscriptionId, 'first_order', $zeroOrderId);

        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'id' => 'in_uncollectible',
                'subscription' => 'sub_zero_uncollectible',
                'amount_paid' => 0,
                'amount_due' => 3900,
                'currency' => 'usd',
                'status' => 'uncollectible',
                'status_transitions' => (object) array('finalized_at' => $paidAt + HOUR_IN_SECONDS),
                'lines' => (object) array(
                    'data' => array(
                        (object) array(
                            'subscription_item' => 'si_uncollectible',
                            'metadata' => (object) array('ppcart_product_id' => 123),
                            'period' => (object) array('end' => $paidAt + MONTH_IN_SECONDS),
                        ),
                    ),
                ),
            ),
            new PPCart_Subscription($subscriptionId),
            'invoice.marked_uncollectible'
        );

        foreach ($this->ordersOf($subscriptionId) as $orderId) {
            $this->createdPostIds[] = $orderId;
        }
        $this->assertSame('paid', ppcart_get_post_meta($zeroOrderId, 'status', true));
        $this->assertSame('', (string) ppcart_get_post_meta($zeroOrderId, 'stripe_invoice_id', true));
    }

    private function syncZeroInvoice(string $invoiceId, string $stripeSubscriptionId, int $subscriptionId, int $paidAt): void
    {
        PPCart_Stripe_Sync::sync_invoice_resource(
            (object) array(
                'id' => $invoiceId,
                'subscription' => $stripeSubscriptionId,
                'amount_paid' => 0,
                'currency' => 'usd',
                'status' => 'paid',
                'status_transitions' => (object) array('paid_at' => $paidAt, 'finalized_at' => $paidAt),
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
            'invoice.paid'
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
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    'meta_query' => array(
                        array(
                            'key' => ppcart_meta_key('subscription_id'),
                            'value' => $subscriptionId,
                        ),
                    ),
                )
            )
        );
        sort($ids);

        return $ids;
    }
}
