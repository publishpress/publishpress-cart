<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Filters;
use PPCart_Admin_Order_List_Controller;
use ReflectionClass;

class PendingCancellationListTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var mixed
     */
    private $previousTypenow;

    protected function setUp(): void
    {
        parent::setUp();

        global $typenow;
        $this->previousTypenow = $typenow;
        $typenow = ppcart_live_post_type('subscription');
        $_GET = [];

        if (! class_exists(PPCart_Admin_Filters::class, false)) {
            require_once dirname(__DIR__, 4) . '/admin/class-ppcart-admin-filters.php';
        }
    }

    protected function tearDown(): void
    {
        global $typenow;
        $typenow = $this->previousTypenow;
        $_GET = [];

        parent::tearDown();
    }

    public function test_IT_424_subscriptions_list_has_a_pending_cancellation_view_with_its_count(): void
    {
        $this->createSubscription([ 'cancel_date' => '2026-11-01', 'cancel_at' => 1795651200 ]);
        $this->createSubscription([ 'cancel_date' => '' ]);
        $this->createSubscription([ 'cancel_date' => '2026-10-01', 'status' => 'canceled' ], 'canceled');

        $filters = (new ReflectionClass(PPCart_Admin_Filters::class))->newInstanceWithoutConstructor();
        $views = $filters->pending_cancellation_view(
            [
                'all'      => '<a href="#">All</a>',
                'active'   => '<a href="#">Active</a>',
                'canceled' => '<a href="#">Canceled</a>',
            ]
        );

        $this->assertSame([ 'all', 'active', 'ppcart_pending_cancellation', 'canceled' ], array_keys($views));
        $this->assertStringContainsString('subscription_type=pending_cancellation', $views['ppcart_pending_cancellation']);
        $this->assertStringContainsString('(1)', $views['ppcart_pending_cancellation']);
    }

    public function test_IT_424_a_subscription_pending_cancellation_shows_when_it_ends_instead_of_a_next_payment(): void
    {
        $pendingId = $this->createSubscription(
            [
                'cancel_date'        => '2026-11-01',
                'cancel_at'          => 1795651200,
                'sub_next_bill_date' => 1795651200,
            ]
        );
        $activeId = $this->createSubscription([ 'sub_next_bill_date' => 1798329600 ]);

        $controller = new PPCart_Admin_Order_List_Controller('ppcart', 'Cart', 'test');

        $status = $this->renderColumn($controller, 'status', $pendingId);
        $this->assertStringContainsString('pending-cancellation', $status);
        $this->assertStringContainsString('Cancels Nov 26, 2026', $status);
        $this->assertSame('-', $this->renderColumn($controller, 'next_payment', $pendingId));

        $this->assertStringContainsString('ppcart-status active', $this->renderColumn($controller, 'status', $activeId));
        $this->assertNotSame('-', $this->renderColumn($controller, 'next_payment', $activeId));
    }

    private function renderColumn(PPCart_Admin_Order_List_Controller $controller, string $column, int $postId): string
    {
        ob_start();
        $controller->custom_subscription_column($column, $postId);

        return trim((string) ob_get_clean());
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function createSubscription(array $meta, string $postStatus = 'active'): int
    {
        $subscriptionId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('subscription'),
                'post_status' => $postStatus,
                'post_title'  => 'Pending cancellation list test',
            ]
        );

        $meta = array_merge([ 'status' => $postStatus ], $meta);
        foreach ($meta as $key => $value) {
            if ('' !== $value) {
                update_post_meta($subscriptionId, ppcart_meta_key($key), $value);
            }
        }

        return (int) $subscriptionId;
    }
}
