<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Order_List_Controller;
use PPCart_Order_Refunds;
use ReflectionClass;
use WP_Query;

class PartiallyRefundedOrdersViewTest extends WPTestCase
{
    /**
     * @var int[]
     */
    private $createdPostIds = [];

    /**
     * @var array<string, mixed>
     */
    private $previousGet = [];

    /**
     * @var \WP_Screen|null
     */
    private $previousScreen;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\WP_Screen::class, false)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
        }
        if (! function_exists('set_current_screen')) {
            require_once ABSPATH . 'wp-admin/includes/screen.php';
        }

        $this->previousGet    = $_GET;
        $this->previousScreen = get_current_screen();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }
        $this->createdPostIds = [];

        $_GET = $this->previousGet;
        $GLOBALS['typenow'] = '';
        if ($this->previousScreen instanceof \WP_Screen) {
            set_current_screen($this->previousScreen);
        } else {
            unset($GLOBALS['current_screen']);
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-416
     */
    public function test_IT_416_lists_only_orders_with_a_refund_below_the_order_amount(): void
    {
        $partialAmount   = $this->insertOrder('paid', '100.00', '30.00');
        $partialLog      = $this->insertOrder('paid', '100.00', null, [
            're_one' => [ 'refundID' => 're_one', 'amount' => '15.00', 'date' => '2026-01-01' ],
            're_two' => [ 'refundID' => 're_two', 'amount' => '10.00', 'date' => '2026-01-02' ],
        ]);
        $partialFormatted = $this->insertOrder('paid', '1,000.00', '250.00');
        $partialManual   = $this->insertOrder('refunded', '100.00', '25.00');
        $fullyRefunded   = $this->insertOrder('refunded', '100.00', '100.00');
        $notRefunded     = $this->insertOrder('paid', '100.00', null);
        $zeroRefund      = $this->insertOrder('paid', '100.00', '0');
        $trashedPartial  = $this->insertOrder('paid', '100.00', '30.00');
        wp_trash_post($trashedPartial);

        $ids = PPCart_Order_Refunds::get_partially_refunded_order_ids(ppcart_live_post_type('order'));

        $this->assertContains($partialAmount, $ids);
        $this->assertContains($partialLog, $ids);
        $this->assertContains($partialFormatted, $ids);
        $this->assertContains($partialManual, $ids, 'A manual partial refund keeps the "Partially refunded" label, so it is listed too.');
        $this->assertNotContains($fullyRefunded, $ids);
        $this->assertNotContains($notRefunded, $ids);
        $this->assertNotContains($zeroRefund, $ids);
        $this->assertNotContains($trashedPartial, $ids);

        foreach ($ids as $id) {
            $this->assertTrue(PPCart_Order_Refunds::is_partially_refunded($id), 'Every listed order shows the "Partially refunded" label.');
        }
    }

    /**
     * @test-id IT-416
     */
    public function test_IT_416_view_link_shows_the_count_after_the_refunded_view(): void
    {
        $postType = ppcart_live_post_type('order');
        $this->insertOrder('paid', '100.00', '30.00');
        $this->insertOrder('refunded', '100.00', '100.00');
        $expected = count(PPCart_Order_Refunds::get_partially_refunded_order_ids($postType));

        $GLOBALS['typenow'] = $postType;
        $_GET = [ 'post_type' => $postType ];

        $views = $this->controller()->order_partially_refunded_view([
            'all'             => '<a href="#">All</a>',
            'ppcart_paid'     => '<a href="#">Paid</a>',
            'ppcart_refunded' => '<a href="#">Refunded</a>',
            'ppcart_failed'   => '<a href="#">Failed</a>',
        ]);

        $this->assertSame(
            [ 'all', 'ppcart_paid', 'ppcart_refunded', 'ppcart_partially_refunded', 'ppcart_failed' ],
            array_keys($views)
        );
        $link = $views['ppcart_partially_refunded'];
        $this->assertStringContainsString('Partially Refunded <span class="count">(' . number_format_i18n($expected) . ')</span>', $link);
        $this->assertStringContainsString('ppcart_refund_status=partial', $link);
        $this->assertStringContainsString('post_type=' . $postType, $link);
        $this->assertStringNotContainsString('current', $link);

        $_GET['ppcart_refund_status'] = 'partial';
        $views = $this->controller()->order_partially_refunded_view([ 'ppcart_refunded' => '<a href="#">Refunded</a>' ]);
        $this->assertStringContainsString('class="current" aria-current="page"', $views['ppcart_partially_refunded']);
    }

    /**
     * @test-id IT-416
     */
    public function test_IT_416_view_is_not_added_to_other_post_types(): void
    {
        $GLOBALS['typenow'] = ppcart_live_post_type('subscription');
        $views = [ 'all' => '<a href="#">All</a>' ];

        $this->assertSame($views, $this->controller()->order_partially_refunded_view($views));
    }

    /**
     * @test-id IT-416
     */
    public function test_IT_416_selected_view_limits_the_orders_list_query(): void
    {
        $postType = ppcart_live_post_type('order');
        $partial  = $this->insertOrder('paid', '100.00', '30.00');
        $full     = $this->insertOrder('refunded', '100.00', '100.00');
        $none     = $this->insertOrder('paid', '100.00', null);

        set_current_screen('edit-' . $postType);
        $_GET = [ 'post_type' => $postType, 'ppcart_refund_status' => 'partial' ];

        $query = new WP_Query();
        $query->set('post_type', $postType);
        $previousMainQuery       = $GLOBALS['wp_the_query'] ?? null;
        $GLOBALS['wp_the_query'] = $query;

        try {
            $this->controller()->order_partially_refunded_filter($query);
        } finally {
            $GLOBALS['wp_the_query'] = $previousMainQuery;
        }

        $postIn = array_map('intval', (array) $query->get('post__in'));
        $this->assertContains($partial, $postIn);
        $this->assertNotContains($full, $postIn);
        $this->assertNotContains($none, $postIn);

        $found = get_posts([
            'post_type'      => $postType,
            'post_status'    => 'any',
            'post__in'       => $postIn,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        $this->assertContains($partial, $found);
        $this->assertNotContains($full, $found);
    }

    private function controller(): PPCart_Admin_Order_List_Controller
    {
        return (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))->newInstanceWithoutConstructor();
    }

    /**
     * @param string               $status       Logical order status.
     * @param string               $amount       Stored order amount.
     * @param string|null          $refundAmount Stored refund_amount meta, or null to leave it unset.
     * @param array<string, mixed> $refundLog    Stored refund_log meta.
     * @return int
     */
    private function insertOrder(string $status, string $amount, ?string $refundAmount, array $refundLog = []): int
    {
        $orderId = wp_insert_post([
            'post_type'   => ppcart_live_post_type('order'),
            'post_status' => $status,
            'post_title'  => 'Partially refunded view order',
        ]);
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = $orderId;

        ppcart_update_post_meta($orderId, 'status', $status);
        ppcart_update_post_meta($orderId, 'amount', $amount);
        if (null !== $refundAmount) {
            ppcart_update_post_meta($orderId, 'refund_amount', $refundAmount);
        }
        if ($refundLog) {
            ppcart_update_post_meta($orderId, 'refund_log', $refundLog);
        }

        return $orderId;
    }
}
