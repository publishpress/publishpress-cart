<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;

class CheckoutCompletionGuardTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var int[]
     */
    private $createdPostIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }
        $this->createdPostIds = [];

        parent::tearDown();
    }

    public function test_IT_362_claim_checkout_complete_fires_once_per_order(): void
    {
        $firstOrder  = $this->createPendingOrder('first-token');
        $secondOrder = $this->createPendingOrder('second-token');

        $this->assertTrue(ppcart_claim_checkout_complete($firstOrder));
        $this->assertFalse(ppcart_claim_checkout_complete($firstOrder));
        $this->assertSame(1, ppcart_checkout_complete_fire_count($firstOrder));

        $this->assertTrue(ppcart_claim_checkout_complete($secondOrder));
        $this->assertSame(1, ppcart_checkout_complete_fire_count($secondOrder));

        $this->assertFalse(ppcart_claim_checkout_complete($firstOrder));
        $this->assertSame(1, ppcart_checkout_complete_fire_count($firstOrder));
    }

    /**
     * @return int
     */
    private function createPendingOrder(string $token): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Guard order',
            ]
        );

        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'status', 'pending-payment');
        ppcart_update_post_meta($orderId, PPCart_Order::INVOICE_TOKEN_META_KEY, $token);

        return (int) $orderId;
    }
}

