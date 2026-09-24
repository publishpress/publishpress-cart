<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Order_List_Controller;
use ReflectionClass;

class BulkActionsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $previousGet;

    /**
     * @var bool
     */
    private $hadGet = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hadGet = array_key_exists('_GET', $GLOBALS);
        $this->previousGet = $_GET;
        $_GET = [];
    }

    protected function tearDown(): void
    {
        if ($this->hadGet) {
            $_GET = $this->previousGet;
        } else {
            $_GET = [];
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-334
     */
    public function test_IT_334_order_and_subscription_bulk_action_keys_are_canonical(): void
    {
        $controller = $this->controller();

        $orderActions = $controller->order_bulk_action([]);
        $this->assertArrayHasKey('ppcart_make_paid', $orderActions);
        $this->assertArrayHasKey('ppcart_make_failed', $orderActions);
        $this->assertArrayHasKey('ppcart_make_pending', $orderActions);
        $this->assertArrayHasKey('ppcart_make_completed', $orderActions);
        $this->assertArrayNotHasKey('sc_make_paid', $orderActions);
        $this->assertArrayNotHasKey('sc_make_failed', $orderActions);
        $this->assertArrayNotHasKey('sc_make_pending', $orderActions);
        $this->assertArrayNotHasKey('sc_make_completed', $orderActions);

        $subscriptionActions = $controller->subscription_bulk_action([]);
        $this->assertArrayHasKey('ppcart_make_active', $subscriptionActions);
        $this->assertArrayHasKey('ppcart_make_canceled', $subscriptionActions);
        $this->assertArrayHasKey('ppcart_make_completed', $subscriptionActions);
        $this->assertArrayHasKey('ppcart_sync_stripe', $subscriptionActions);
        $this->assertArrayNotHasKey('sc_make_active', $subscriptionActions);
        $this->assertArrayNotHasKey('sc_make_canceled', $subscriptionActions);
        $this->assertArrayNotHasKey('sc_make_completed', $subscriptionActions);
        $this->assertArrayNotHasKey('sc_sync_stripe', $subscriptionActions);
    }

    /**
     * @test-id IT-334
     */
    public function test_IT_334_sync_handler_redirects_with_canonical_query_arg(): void
    {
        $redirect = $this->controller()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=ppcart_subscription',
            'ppcart_sync_stripe',
            []
        );

        $this->assertStringContainsString('bulk_ppcart_sync_stripe', $redirect);
        $this->assertStringNotContainsString('bulk_sc_sync_stripe', $redirect);
    }

    /**
     * @test-id IT-334
     */
    public function test_IT_334_status_handler_redirects_with_canonical_query_arg(): void
    {
        $redirect = $this->controller()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=ppcart_order',
            'ppcart_make_paid',
            []
        );

        $this->assertStringContainsString('bulk_ppcart_make_paid', $redirect);
        $this->assertStringNotContainsString('bulk_sc_make_paid', $redirect);
    }

    /**
     * @test-id IT-334
     */
    public function test_IT_334_sync_notice_reads_canonical_get_key_not_leftover(): void
    {
        $controller = $this->controller();

        $_GET['bulk_ppcart_sync_stripe'] = '1';
        ob_start();
        $controller->sync_stripe_bulk_notice();
        $canonical = (string) ob_get_clean();
        $this->assertStringContainsString('notice', $canonical);
        $this->assertStringContainsString('subscription', $canonical);

        $_GET = [];
        $_GET['bulk_sc_sync_stripe'] = '1';
        ob_start();
        $controller->sync_stripe_bulk_notice();
        $leftover = (string) ob_get_clean();
        $this->assertSame('', $leftover);
    }

    private function controller(): PPCart_Admin_Order_List_Controller
    {
        return (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))
            ->newInstanceWithoutConstructor();
    }
}
