<?php

declare(strict_types=1);

namespace Tests\Integration\Account;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Files;
use PPCart_Order;
use PPCart_Public_Account_Controller;

class AccountShortcodeKsesTest extends WPTestCase
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

    /**
     * @test-id IT-369
     */
    public function test_IT_369_my_account_shortcode_strips_script_from_tab_filter(): void
    {
        $userId = $this->createCustomer();
        wp_set_current_user($userId);

        $inject = static function ($ret) {
            return (string) $ret . '<script>alert(1)</script><p class="ppcart-kses-ok">ok</p>';
        };
        add_filter('ppcart_my_account_tab_content', $inject);

        $controller = new PPCart_Public_Account_Controller();
        $html       = (string) $controller->my_account_page_shortcode([], null);

        remove_filter('ppcart_my_account_tab_content', $inject);

        $this->assertStringContainsString('ppcart-my-account', $html);
        $this->assertStringContainsString('ppcart-kses-ok', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    /**
     * @test-id IT-369
     */
    public function test_IT_369_downloads_shortcode_preserves_allowed_markup(): void
    {
        $userId  = $this->createCustomer();
        $orderId = $this->createPaidOrderWithDownload($userId, 'secret-token');
        wp_set_current_user($userId);

        $html = (string) (new PPCart_Files())->downloads_shortcode(
            [
                'id'   => $orderId,
                'full' => false,
            ]
        );

        $this->assertStringContainsString('ppcart-download-list', $html);
        $this->assertStringContainsString('Handbook.pdf', $html);
        $this->assertStringContainsString('<a href', $html);
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
                'post_title'  => 'Kses Order',
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
                'file_id'             => 'f1',
                'order_id'            => $orderId,
                'order_key'           => 'accesskey' . $orderId,
                'product_id'          => $productId,
                'downloads_remaining'   => 'unlimited',
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
