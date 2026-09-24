<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Stripe_Sync_Context;
use PPCart_Order;
use PPCart_Subscription;
use Tests\Support\Integration\StripeSyncTestCase;

class OwnedFieldsTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_013_status_meta_key_is_recognized_as_stripe_owned(): void
    {
        $this->assertTrue(ppcart_is_stripe_owned_field('status'));
    }

    public function test_IT_014_order_payment_status_is_recognized_as_stripe_owned(): void
    {
        $this->assertTrue(ppcart_is_stripe_owned_field('payment_status', new PPCart_Order()));
    }

    public function test_IT_015_order_customer_email_stays_locally_editable(): void
    {
        $this->assertFalse(ppcart_is_stripe_owned_field('email', new PPCart_Order()));
    }

    public function test_IT_016_subscription_next_bill_date_is_recognized_as_stripe_owned(): void
    {
        $this->assertTrue(ppcart_is_stripe_owned_field('sub_next_bill_date', new PPCart_Subscription()));
    }

    public function test_IT_017_subscription_customer_first_name_stays_locally_editable(): void
    {
        $this->assertFalse(ppcart_is_stripe_owned_field('first_name', new PPCart_Subscription()));
    }

    public function test_IT_018_data_guard_reverts_local_edits_to_a_stripe_order_outside_sync_context(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_current',
                'refund_log' => array(
                    're_current' => array(
                        'refundID' => 're_current',
                    ),
                ),
            )
        );

        $changedOrder = new PPCart_Order($orderId);
        $changedOrder->status = 'refunded';
        $changedOrder->payment_status = 'refunded';
        $changedOrder->transaction_id = 'ch_local_change';
        $changedOrder->refund_log = array(
            're_local_change' => array(
                'refundID' => 're_local_change',
            ),
        );

        $preservedOrder = PPCart_Stripe_Sync::preserve_owned_fields($changedOrder);

        $this->assertSame('paid', $preservedOrder->status);
        $this->assertSame('paid', $preservedOrder->payment_status);
        $this->assertSame('ch_current', $preservedOrder->transaction_id);
        $this->assertArrayHasKey('re_current', (array) $preservedOrder->refund_log);
    }

    public function test_IT_019_data_guard_allows_order_writes_inside_a_sync_context(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_current',
            )
        );

        $contextOrder = new PPCart_Order($orderId);
        $contextOrder->status = 'refunded';
        $contextOrder->payment_status = 'refunded';
        $contextOrder->transaction_id = 'ch_context_change';

        $contextOrder = PPCart_Stripe_Sync_Context::run(
            function () use ($contextOrder) {
                return PPCart_Stripe_Sync::preserve_owned_fields($contextOrder);
            }
        );

        $this->assertSame('refunded', $contextOrder->status);
        $this->assertSame('ch_context_change', $contextOrder->transaction_id);
    }

    public function test_IT_020_data_guard_reverts_local_edits_to_a_stripe_subscription_outside_sync_context(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_current',
                'sub_next_bill_date' => '111',
            )
        );

        $changedSubscription = new PPCart_Subscription($subscriptionId);
        $changedSubscription->status = 'canceled';
        $changedSubscription->sub_status = 'canceled';
        $changedSubscription->subscription_id = 'sub_local_change';
        $changedSubscription->sub_next_bill_date = '222';

        $preservedSubscription = PPCart_Stripe_Sync::preserve_owned_fields($changedSubscription);

        $this->assertSame('active', $preservedSubscription->status);
        $this->assertSame('active', $preservedSubscription->sub_status);
        $this->assertSame('sub_current', $preservedSubscription->subscription_id);
        $this->assertSame('111', $preservedSubscription->sub_next_bill_date);
    }

    public function test_IT_021_data_guard_leaves_a_non_stripe_cod_order_editable(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'pay_method' => 'cod',
                'status' => 'paid',
                'payment_status' => 'paid',
            )
        );

        $nonStripeOrder = new PPCart_Order($orderId);
        $nonStripeOrder->status = 'paid';
        $nonStripeOrder->payment_status = 'paid';

        $nonStripeOrder = PPCart_Stripe_Sync::preserve_owned_fields($nonStripeOrder);

        $this->assertSame('paid', $nonStripeOrder->status);
    }

    public function test_IT_022_backed_post_helper_detects_a_stripe_pay_method_order(): void
    {
        $postId = $this->createStripeOrder();
        ppcart_update_post_meta($postId, 'pay_method', 'stripe');

        $this->assertTrue(ppcart_is_stripe_backed_post($postId));
    }

    public function test_IT_023_backed_post_helper_detects_a_stripe_subscription_via_current_meta(): void
    {
        $postId = wp_insert_post(
            array(
                'post_type' => ppcart_live_post_type('subscription'),
                'post_status' => 'publish',
                'post_title' => 'Stripe Meta Subscription',
            )
        );
        $this->createdPostIds[] = (int) $postId;
        ppcart_update_post_meta($postId, 'stripe_subscription_id', 'sub_meta');

        $this->assertTrue(ppcart_is_stripe_backed_post($postId));
    }

    public function test_IT_024_backed_post_helper_detects_a_stripe_subscription_via_legacy_meta(): void
    {
        $postId = wp_insert_post(
            array(
                'post_type' => ppcart_live_post_type('subscription'),
                'post_status' => 'publish',
                'post_title' => 'Legacy Stripe Meta Subscription',
            )
        );
        $this->createdPostIds[] = (int) $postId;
        ppcart_update_post_meta($postId, 'subscription_id', 'sub_legacy_meta');

        $this->assertTrue(ppcart_is_stripe_backed_post($postId));
    }

    public function test_IT_025_resource_event_meta_is_recorded_under_the_sc_prefix(): void
    {
        $subscriptionId = $this->createStripeSubscription();
        $resourceEvent = (object) array(
            'id' => 'evt_resource',
            'type' => 'customer.subscription.updated',
        );

        PPCart_Stripe_Sync::mark_resource_event($subscriptionId, $resourceEvent);

        $this->assertSame('evt_resource', ppcart_get_post_meta($subscriptionId, 'last_stripe_event_id', true));
        $this->assertSame('customer.subscription.updated', ppcart_get_post_meta($subscriptionId, 'last_stripe_event_type', true));
        $this->assertNotSame('', ppcart_get_post_meta($subscriptionId, 'last_stripe_sync', true));
    }
}
