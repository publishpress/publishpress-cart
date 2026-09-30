<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Order_List_Controller;
use ReflectionClass;

class OrderBulkActionCapabilitiesTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-392
     */
    public function test_IT_392_bulk_status_action_skips_order_user_cannot_edit(): void
    {
        $orderId = (int) $this->factory()->post->create(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Capability-guarded order',
            ]
        );
        $subscriberId = (int) $this->factory()->user->create([ 'role' => 'subscriber' ]);
        $updatedOrderIds = [];

        add_action(
            'ppcart_order_updated',
            static function ($order) use (&$updatedOrderIds) {
                $updatedOrderIds[] = (int) $order->id;
            }
        );
        wp_set_current_user($subscriberId);

        $this->assertFalse(current_user_can('edit_post', $orderId));

        $this->controller()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=ppcart_order',
            'ppcart_make_paid',
            [ $orderId ]
        );

        $this->assertSame([], $updatedOrderIds);
        $this->assertSame('publish', get_post_status($orderId));
    }

    /**
     * @test-id IT-392
     */
    public function test_IT_392_bulk_stripe_sync_skips_subscription_user_cannot_edit(): void
    {
        $subscriptionId = (int) $this->factory()->post->create(
            [
                'post_type'   => ppcart_live_post_type('subscription'),
                'post_status' => 'publish',
                'post_title'  => 'Capability-guarded subscription',
            ]
        );
        ppcart_update_post_meta($subscriptionId, 'pay_method', 'stripe');
        ppcart_update_post_meta($subscriptionId, 'subscription_id', 'sub_capability_guard');

        $subscriberId = (int) $this->factory()->user->create([ 'role' => 'subscriber' ]);
        wp_set_current_user($subscriberId);

        $this->assertFalse(current_user_can('edit_post', $subscriptionId));

        $redirect = $this->controller()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=ppcart_subscription',
            'ppcart_sync_stripe',
            [ $subscriptionId ]
        );

        $this->assertStringContainsString('bulk_ppcart_sync_stripe=0', $redirect);
        $this->assertStringNotContainsString('bulk_ppcart_sync_stripe_failed', $redirect);
    }

    private function controller(): PPCart_Admin_Order_List_Controller
    {
        return (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))
            ->newInstanceWithoutConstructor();
    }
}
