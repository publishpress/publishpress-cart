<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Screens;
use PPCart_Product_Metabox_Option_Sources;

/**
 * Cart admin option lists must not change the global $post of the screen
 * they run on, for example another plugin's edit screen.
 */
class GlobalPostIsolationTest extends WPTestCase
{
    private const GLOBALS = [ 'post', 'wp_query', 'hook_suffix', 'current_screen', 'pagenow', 'typenow' ];

    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $originalGlobals = [];

    /**
     * @var array<string, mixed>
     */
    private $originalGet = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::GLOBALS as $name) {
            $this->originalGlobals[ $name ] = $GLOBALS[ $name ] ?? null;
        }
        $this->originalGet = $_GET;

        register_post_type('it_event', [ 'public' => true, 'show_ui' => true ]);
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        foreach (self::GLOBALS as $name) {
            $GLOBALS[ $name ] = $this->originalGlobals[ $name ];
        }
        unregister_post_type('it_event');

        parent::tearDown();
    }

    /**
     * @test-id IT-481
     */
    public function test_IT_481_cart_option_lists_leave_the_edited_post_of_another_plugin_alone(): void
    {
        $eventId = self::factory()->post->create([ 'post_type' => 'it_event', 'post_status' => 'publish', 'post_title' => 'Summer event' ]);
        $productId = self::factory()->post->create([ 'post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish', 'post_title' => 'Cart product' ]);
        $pathId = self::factory()->post->create([ 'post_type' => ppcart_query_pro_post_types('us_path')[0], 'post_status' => 'publish', 'post_title' => 'Cart offer' ]);

        $this->openEditScreen($eventId, 'it_event');
        $event = get_post($eventId);
        $GLOBALS['post'] = $event;

        $paths = PPCart_Product_Metabox_Option_Sources::upsell_paths();
        $products = PPCart_Product_Metabox_Option_Sources::product_options();

        $this->assertSame('Cart offer (ID: ' . $pathId . ')', $paths[ $pathId ]);
        $this->assertSame('Cart product (ID: ' . $productId . ')', $products[ $productId ]);
        $this->assertSame($event, $GLOBALS['post']);
        $this->assertSame($eventId, get_the_ID());
        $this->assertSame('Summer event', get_the_title());
    }

    /**
     * @test-id IT-482
     */
    public function test_IT_482_screen_context_uses_the_screen_post_type_not_the_global_post_or_a_post_id(): void
    {
        $eventId = self::factory()->post->create([ 'post_type' => 'it_event', 'post_status' => 'publish' ]);
        $productId = self::factory()->post->create([ 'post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish' ]);
        $productType = ppcart_live_post_type('product');

        // Another plugin's edit screen, with the global $post left on a Cart product.
        $this->openEditScreen($eventId, 'it_event');
        $GLOBALS['post'] = get_post($productId);
        $this->assertFalse(PPCart_Admin_Screens::is_post_type_screen($productType, 'post.php'));
        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen('post.php'));

        // The Pages list with a Cart product ID in its URL.
        $_GET = [ 'post_type' => 'page', 'post' => (string) $productId ];
        $GLOBALS['pagenow'] = 'edit.php';
        $GLOBALS['hook_suffix'] = 'edit.php';
        $GLOBALS['typenow'] = 'page';
        set_current_screen('edit-page');
        $this->assertFalse(PPCart_Admin_Screens::is_post_type_screen($productType, 'edit.php'));
        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen('edit.php'));

        // A Cart product's edit screen, with the global $post left on another post.
        $this->openEditScreen($productId, $productType);
        $GLOBALS['post'] = get_post($eventId);
        $this->assertTrue(PPCart_Admin_Screens::is_post_type_screen($productType, 'post.php'));
        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen('post.php'));
    }

    /**
     * @test-id IT-489
     */
    public function test_IT_489_scheduled_report_data_counts_orders_and_refunds_and_keeps_the_global_post(): void
    {
        $orderType = ppcart_live_post_type('order');
        foreach ([ [ 'paid', 25 ], [ 'completed', 15 ], [ 'pending-payment', 40 ] ] as $order) {
            $orderId = self::factory()->post->create([ 'post_type' => $orderType, 'post_status' => 'publish' ]);
            ppcart_update_post_meta($orderId, 'status', $order[0]);
            ppcart_update_post_meta($orderId, 'payment_status', $order[0]);
            ppcart_update_post_meta($orderId, 'amount', $order[1]);
        }
        $refundedId = self::factory()->post->create([ 'post_type' => $orderType, 'post_status' => 'refunded' ]);
        ppcart_update_post_meta($refundedId, 'status', 'refunded');
        ppcart_update_post_meta($refundedId, 'payment_status', 'refunded');
        ppcart_update_post_meta($refundedId, 'amount', 30);
        ppcart_update_post_meta($refundedId, 'refund_log', [ 're_1' => [ 'refundID' => 're_1', 'date' => '2026-11-27 10:00', 'amount' => '12.5' ] ]);

        $page = self::factory()->post->create_and_get([ 'post_type' => 'page' ]);
        $GLOBALS['post'] = $page;

        if (! function_exists('ppcart_schedule_report_data')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/scheduled-reports/report-data.php';
        }
        $data = ppcart_schedule_report_data();

        $this->assertSame(2, $data['completed_orders']);
        $this->assertSame(1, $data['pending_payment']);
        $this->assertEquals(110, $data['carttotal']['total']);
        $this->assertEquals(12.5, $data['refunded_amount']);
        $this->assertSame(1, $data['refunded_time']);
        $this->assertSame($page, $GLOBALS['post']);
    }

    /**
     * @test-id IT-490
     */
    public function test_IT_490_the_privacy_exporter_exports_each_order_and_keeps_the_global_post(): void
    {
        $orderIds = [];
        foreach ([ 'Ada', 'Grace' ] as $firstName) {
            $orderId = self::factory()->post->create([ 'post_type' => ppcart_live_post_type('order'), 'post_status' => 'publish' ]);
            ppcart_update_post_meta($orderId, 'email', 'export@example.test');
            ppcart_update_post_meta($orderId, 'firstname', $firstName);
            $orderIds[ $orderId ] = $firstName;
        }
        $otherId = self::factory()->post->create([ 'post_type' => ppcart_live_post_type('order'), 'post_status' => 'publish' ]);
        ppcart_update_post_meta($otherId, 'email', 'someone-else@example.test');

        $page = self::factory()->post->create_and_get([ 'post_type' => 'page' ]);
        $GLOBALS['post'] = $page;

        $admin = (new \ReflectionClass(\PPCart_Admin::class))->newInstanceWithoutConstructor();
        $result = $admin->user_data_exporter('export@example.test');

        $this->assertTrue($result['done']);
        $exported = [];
        foreach ($result['data'] as $item) {
            $values = array_column($item['data'], 'value', 'name');
            $exported[ (int) $values['Order ID'] ] = $values['First Name'];
            $this->assertSame('order-' . $values['Order ID'], $item['item_id']);
            $this->assertSame('export@example.test', $values['Email']);
        }
        ksort($exported);
        ksort($orderIds);
        $this->assertSame($orderIds, $exported);
        $this->assertSame($page, $GLOBALS['post']);
    }

    /**
     * Set up wp-admin/post.php for a post: the main query has no post, as on that screen.
     */
    private function openEditScreen(int $postId, string $postType): void
    {
        $_GET = [ 'post' => (string) $postId, 'action' => 'edit' ];
        $GLOBALS['wp_query'] = new \WP_Query();
        $GLOBALS['pagenow'] = 'post.php';
        $GLOBALS['hook_suffix'] = 'post.php';
        $GLOBALS['typenow'] = $postType;
        set_current_screen($postType);
    }
}
