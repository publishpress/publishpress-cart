<?php

declare(strict_types=1);

namespace Tests\Integration\OrderAccess;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Files;
use PPCart_Order;

class OrderAccessTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var int[]
     */
    private $createdPostIds = [];

    /**
     * @var int[]
     */
    private $createdUserIds = [];

    /**
     * @var int[]
     */
    private $createdDownloadIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        wp_set_current_user(0);
        $_GET  = [];
        $_POST = [];

        if (! function_exists('ppcart_do_payment_confirmation')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }
    }

    protected function tearDown(): void
    {
        global $wpdb;

        wp_set_current_user(0);
        $_GET  = [];
        $_POST = [];

        foreach ($this->createdDownloadIds as $downloadId) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Test cleanup of plugin-owned download rows.
            $wpdb->delete(PPCart_Files::live_table(), [ 'download_id' => $downloadId ], [ '%d' ]);
        }
        $this->createdDownloadIds = [];

        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }
        $this->createdPostIds = [];

        foreach ($this->createdUserIds as $userId) {
            wp_delete_user($userId);
        }
        $this->createdUserIds = [];

        parent::tearDown();
    }

    public function test_IT_321_stranger_with_only_order_id_cannot_view(): void
    {
        $orderId = $this->createPaidOrder(0, 'secret-token');
        $_GET['ppcart-order'] = (string) $orderId;

        $this->assertFalse(PPCart_Order::visitor_can_view($orderId));
        $this->assertSame('', (string) (new PPCart_Files())->downloads_shortcode([ 'id' => $orderId, 'full' => false ]));
        $this->assertSame('', (string) ppcart_order_detail([ 'field' => 'email' ]));
    }

    public function test_IT_322_guest_with_matching_token_can_view(): void
    {
        $orderId = $this->createPaidOrderWithDownload(0, 'secret-token');
        $_GET['token'] = 'secret-token';
        $_GET['ppcart-order'] = (string) $orderId;

        $this->assertTrue(PPCart_Order::visitor_can_view($orderId));
        $html = (string) (new PPCart_Files())->downloads_shortcode([ 'id' => $orderId, 'full' => false ]);
        $this->assertStringContainsString('Handbook.pdf', $html);
        $this->assertSame('guest@example.test', ppcart_order_detail([ 'field' => 'email' ]));
    }

    public function test_IT_323_owner_without_token_can_view(): void
    {
        $userId  = $this->createCustomer();
        $orderId = $this->createPaidOrderWithDownload($userId, 'secret-token');
        wp_set_current_user($userId);

        $this->assertTrue(PPCart_Order::visitor_can_view($orderId));
        $html = (string) (new PPCart_Files())->downloads_shortcode([ 'id' => $orderId, 'full' => false ]);
        $this->assertStringContainsString('Handbook.pdf', $html);
    }

    public function test_IT_324_admin_bypasses_public_page_proof(): void
    {
        $orderId = $this->createPaidOrderWithDownload(0, 'secret-token');
        $adminId = $this->factory()->user->create([ 'role' => 'administrator' ]);
        $this->createdUserIds[] = (int) $adminId;
        wp_set_current_user($adminId);

        $this->assertTrue(PPCart_Order::visitor_can_view($orderId));
        $html = (string) (new PPCart_Files())->downloads_shortcode([ 'id' => $orderId, 'full' => false ]);
        $this->assertStringContainsString('Handbook.pdf', $html);
    }

    public function test_IT_325_trusted_email_caller_emits_download_links_without_login(): void
    {
        $orderId = $this->createPaidOrderWithDownload(0, 'secret-token');
        wp_set_current_user(0);
        $orderInfo = (new PPCart_Order($orderId))->get_data();
        $this->assertIsArray($orderInfo);

        $email = ppcart_build_product_notification_email(
            [
                'send_to' => 'purchaser',
                'subject' => 'Your files',
                'message' => 'Files [ppcart_order_downloads] here',
            ],
            $orderInfo
        );

        $this->assertStringContainsString('Handbook.pdf', $email['body']);
        $this->assertStringContainsString('/file/', $email['body']);
    }

    public function test_IT_326_child_order_is_not_unlocked_by_parent_token(): void
    {
        $parentId = $this->createPaidOrder(0, 'parent-token');
        $childId  = $this->createPaidOrder(0, 'child-token');
        $_GET['token'] = 'parent-token';

        $this->assertTrue(PPCart_Order::visitor_can_view($parentId));
        $this->assertFalse(PPCart_Order::visitor_can_view($childId));
        $this->assertSame('', (string) (new PPCart_Files())->downloads_shortcode([ 'id' => $childId, 'full' => false ]));
    }

    public function test_IT_321_confirmation_without_proof_matches_missing_order(): void
    {
        $orderId = $this->createPaidOrder(0, 'secret-token');
        $previousProduct = $GLOBALS['ppcart_product'] ?? null;
        $GLOBALS['ppcart_product'] = (object) [
            'checkout_ended_message'   => 'Closed now',
            'confirmation_message'  => 'Thanks buyer',
        ];

        $_GET['ppcart-order'] = (string) $orderId;
        ob_start();
        ppcart_do_payment_confirmation(0);
        $withoutProof = (string) ob_get_clean();

        unset($_GET['ppcart-order']);
        ob_start();
        ppcart_do_payment_confirmation(0);
        $missingOrder = (string) ob_get_clean();

        $GLOBALS['ppcart_product'] = $previousProduct;

        $this->assertStringContainsString('Closed now', $withoutProof);
        $this->assertStringContainsString('Closed now', $missingOrder);
        $this->assertStringNotContainsString('Thanks buyer', $withoutProof);
    }

    /**
     * @param int    $userAccount User id, or 0 for a guest.
     * @param string $token       Order access token.
     * @return int
     */
    private function createPaidOrder(int $userAccount, string $token): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Access Order',
            ]
        );
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'user_account', $userAccount);
        ppcart_update_post_meta($orderId, 'status', 'paid');
        ppcart_update_post_meta($orderId, 'email', 'guest@example.test');
        ppcart_update_post_meta($orderId, 'amount', 10);
        ppcart_update_post_meta($orderId, PPCart_Order::INVOICE_TOKEN_META_KEY, $token);

        return (int) $orderId;
    }

    /**
     * @param int    $userAccount User id, or 0 for a guest.
     * @param string $token       Order access token.
     * @return int
     */
    private function createPaidOrderWithDownload(int $userAccount, string $token): int
    {
        global $wpdb;

        $orderId   = $this->createPaidOrder($userAccount, $token);
        $productId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'Download Product',
            ]
        );
        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        $this->createdPostIds[] = (int) $productId;

        ppcart_update_post_meta(
            $productId,
            'files',
            [
                [
                    'file_id'   => 'f1',
                    'file_name' => 'Handbook.pdf',
                    'file_url'  => 'https://example.test/handbook.pdf',
                ],
            ]
        );
        ppcart_update_post_meta($orderId, 'product_id', $productId);
        ppcart_update_post_meta($orderId, 'product_name', 'Download Product');

        $files = new PPCart_Files();
        $files->setup_download_table();
        $inserted = $wpdb->insert(
            PPCart_Files::live_table(),
            [
                'file_id'              => 'f1',
                'order_id'             => $orderId,
                'order_key'            => 'accesskey' . $orderId,
                'product_id'           => $productId,
                'downloads_remaining'  => 'unlimited',
            ]
        );
        $this->assertNotFalse($inserted);
        $this->createdDownloadIds[] = (int) $wpdb->insert_id;

        return $orderId;
    }

    /**
     * @return int
     */
    private function createCustomer(): int
    {
        $userId = $this->factory()->user->create([ 'role' => 'subscriber' ]);
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
        $this->createdUserIds[] = (int) $userId;

        return (int) $userId;
    }
}
