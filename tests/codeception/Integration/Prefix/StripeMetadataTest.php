<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use PPCart_Stripe_Sync;
use ReflectionMethod;
use Tests\Support\Integration\StripeSyncTestCase;

class StripeMetadataTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-327
     */
    public function test_IT_327_canonical_charge_metadata_matches_with_compatibility_mode_off(): void
    {
        $orderId = $this->createStripeOrder(
            [
                'transaction_id' => 'pi_unrelated_canonical',
            ]
        );

        $charge = (object) [
            'id' => 'ch_canonical_only',
            'payment_intent' => 'pi_other_canonical',
            'metadata' => (object) [
                'ppcart_order_id' => $orderId,
                'sc_order_id' => 999999,
            ],
        ];

        $found = $this->findOrderForCharge($charge);
        $this->assertNotFalse($found);
        $this->assertSame($orderId, (int) $found->id);
    }

    /**
     * @param object $charge Stripe charge fixture.
     * @return \PPCart_Order|false
     */
    private function findOrderForCharge($charge)
    {
        $method = new ReflectionMethod(PPCart_Stripe_Sync::class, 'find_order_for_charge');
        $method->setAccessible(true);

        return $method->invoke(null, $charge);
    }
}
