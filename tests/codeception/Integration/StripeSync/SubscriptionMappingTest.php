<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use Tests\Support\Integration\StripeSyncTestCase;

class SubscriptionMappingTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function stripeStatusProvider(): array
    {
        return array(
            'active maps to active' => array('active', 'active'),
            'trialing maps to trialing' => array('trialing', 'trialing'),
            'past_due maps to past_due' => array('past_due', 'past_due'),
            'unpaid maps to unpaid' => array('unpaid', 'unpaid'),
            'incomplete maps to incomplete' => array('incomplete', 'incomplete'),
            'incomplete_expired maps to canceled' => array('incomplete_expired', 'canceled'),
            'canceled maps to canceled' => array('canceled', 'canceled'),
            'paused maps to paused' => array('paused', 'paused'),
            'future_status maps to past_due' => array('future_status', 'past_due'),
        );
    }

    /**
     * @dataProvider stripeStatusProvider
     */
    public function test_IT_045_stripe_subscription_status_maps_to_the_local_publishpress_cart_status(string $stripeStatus, string $expectedStatus): void
    {
        $mapped = PPCart_Stripe_Sync::map_subscription(
            (object) array(
                'status' => $stripeStatus,
                'current_period_end' => 1234567890,
                'cancel_at' => 0,
            )
        );

        $this->assertSame($expectedStatus, $mapped['status']);
    }

    public function test_IT_046_incomplete_expired_preserves_the_raw_stripe_status_in_sub_status(): void
    {
        $mapped = PPCart_Stripe_Sync::map_subscription(
            (object) array(
                'status' => 'incomplete_expired',
                'current_period_end' => 1234567890,
                'cancel_at' => 0,
            )
        );

        $this->assertSame('incomplete_expired', $mapped['sub_status']);
    }

    public function test_IT_047_active_subscription_with_pause_collection_void_maps_to_local_paused(): void
    {
        $mapped = PPCart_Stripe_Sync::map_subscription(
            (object) array(
                'status' => 'active',
                'current_period_end' => 1234567890,
                'cancel_at' => 0,
                'pause_collection' => (object) array(
                    'behavior' => 'void',
                ),
            )
        );

        $this->assertSame('paused', $mapped['status']);
    }

    public function test_IT_048_paused_subscriptions_clear_the_local_next_bill_date(): void
    {
        $mapped = PPCart_Stripe_Sync::map_subscription(
            (object) array(
                'status' => 'active',
                'current_period_end' => 1234567890,
                'cancel_at' => 0,
                'pause_collection' => (object) array(
                    'behavior' => 'void',
                ),
            )
        );

        $this->assertSame('', $mapped['sub_next_bill_date']);
    }

    public function test_IT_049_a_scheduled_cancel_at_clears_the_local_next_bill_date(): void
    {
        $mapped = PPCart_Stripe_Sync::map_subscription(
            (object) array(
                'status' => 'active',
                'current_period_end' => 1234567890,
                'cancel_at' => 1234567890,
            )
        );

        $this->assertSame('', $mapped['sub_next_bill_date']);
    }
}
