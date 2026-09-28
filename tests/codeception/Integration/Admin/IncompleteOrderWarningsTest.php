<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;

class IncompleteOrderWarningsTest extends WPTestCase
{
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

    /**
     * @test-id IT-381
     */
    public function test_IT_381_item_builder_ignores_an_order_created_from_a_non_order_post(): void
    {
        $productId = wp_insert_post(
            [
                'post_type' => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title' => 'Not an Order',
            ]
        );

        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        $this->createdPostIds[] = $productId;

        $order = new PPCart_Order($productId);

        $this->assertFalse($order->id);
        $this->assertSame([], ppcart_get_order_items($order));
    }
}
