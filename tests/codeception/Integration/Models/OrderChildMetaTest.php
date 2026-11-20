<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;

class OrderChildMetaTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_422_an_order_with_a_malformed_child_order_row_still_loads(): void
    {
        $parentId = $this->createOrder();
        $childId = $this->createOrder();
        $serializedChildId = $this->createOrder();

        $key = ppcart_meta_key('order_child');
        add_post_meta($parentId, $key, [ 'id' => $childId, 'type' => 'bump' ]);
        // A row that holds a serialized string instead of an array.
        add_post_meta($parentId, $key, serialize([ 'id' => $serializedChildId, 'type' => 'bump' ]));
        // Rows that hold no order ID at all.
        add_post_meta($parentId, $key, 'not-an-order');
        add_post_meta($parentId, $key, [ 'type' => 'bump' ]);

        $order = new PPCart_Order($parentId);
        $data = $order->get_data();
        $children = $order->get_children();

        $this->assertSame(
            [ $childId, $serializedChildId ],
            array_map('intval', array_column($data['order_child'], 'id'))
        );
        $this->assertCount(2, $children);
    }

    private function createOrder(): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Order child meta test',
            ]
        );

        update_post_meta($orderId, ppcart_meta_key('status'), 'paid');
        update_post_meta($orderId, ppcart_meta_key('payment_status'), 'paid');
        update_post_meta($orderId, ppcart_meta_key('amount'), 10);
        update_post_meta($orderId, ppcart_meta_key('main_offer_amt'), 10);

        return (int) $orderId;
    }
}
