<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Activator;
use PPCart_Admin_Legacy_Tracking_Notice_Controller;
use PPCart_Files;
use PPCart_Order_Admin;

class MutatorCapabilitiesTest extends WPTestCase
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

    /**
     * @var \WP_Screen|null
     */
    private $previousScreen;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(PPCart_Activator::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-activator.php';
        }

        PPCart_Activator::add_cap();
        $this->enableAdminScreen();

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
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Test cleanup.
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

        if ($this->previousScreen instanceof \WP_Screen) {
            set_current_screen($this->previousScreen);
        } else {
            unset($GLOBALS['current_screen']);
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-365
     */
    public function test_IT_365_subscriber_author_cannot_save_order_with_valid_nonce(): void
    {
        $subscriberId = $this->createSubscriber();
        $orderId      = $this->createLoggedOrder($subscriberId, 'Original');
        wp_set_current_user($subscriberId);

        $this->assertFalse(current_user_can('edit_post', $orderId));

        $this->submitOrderSave($orderId, 'Hacked');

        $this->assertSame('Original', (string) ppcart_get_post_meta($orderId, 'firstname', true));
    }

    /**
     * @test-id IT-365
     */
    public function test_IT_365_cart_manager_can_save_order_with_valid_nonce(): void
    {
        $subscriberId = $this->createSubscriber();
        $orderId      = $this->createLoggedOrder($subscriberId, 'Original');
        $managerId    = $this->factory()->user->create([ 'role' => ppcart_live_role('cart_manager') ]);
        $this->createdUserIds[] = (int) $managerId;
        wp_set_current_user($managerId);

        $this->assertTrue(current_user_can('edit_post', $orderId));

        $this->submitOrderSave($orderId, 'StaffEdit');

        $this->assertSame('StaffEdit', (string) ppcart_get_post_meta($orderId, 'firstname', true));
    }

    /**
     * @test-id IT-365
     */
    public function test_IT_365_subscriber_cannot_update_order_downloads_with_valid_nonce(): void
    {
        global $wpdb;

        $subscriberId = $this->createSubscriber();
        $fixture      = $this->createPaidOrderWithDownload($subscriberId);
        $orderId      = $fixture['order_id'];
        $downloadId   = $fixture['download_id'];
        wp_set_current_user($subscriberId);

        $this->submitDownloadSave($orderId, $downloadId, '3');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Assert download row unchanged.
        $remaining = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT downloads_remaining FROM ' . PPCart_Files::live_table() . ' WHERE download_id = %d',
                $downloadId
            )
        );
        $this->assertSame('unlimited', (string) $remaining);
    }

    /**
     * @test-id IT-365
     */
    public function test_IT_365_subscriber_cannot_dismiss_legacy_tracking_notice(): void
    {
        $subscriberId = $this->createSubscriber();
        wp_set_current_user($subscriberId);

        $_REQUEST['ppcart_dismiss_legacy_tracking_notice'] = '1';
        $_REQUEST['_wpnonce']                            = wp_create_nonce('ppcart_dismiss_legacy_tracking_notice');
        $_GET                                            = $_REQUEST;

        $this->assertNotFalse(
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])),
                'ppcart_dismiss_legacy_tracking_notice'
            )
        );

        $controller = new PPCart_Admin_Legacy_Tracking_Notice_Controller();
        $controller->maybe_dismiss_notice();

        $this->assertSame('', (string) get_user_meta($subscriberId, '_ppcart_dismiss_admin_notice_legacy_tracking', true));

        $_GET     = [];
        $_REQUEST = [];
    }

    /**
     * @test-id IT-365
     */
    public function test_IT_365_subscriber_can_update_own_profile_via_ajax(): void
    {
        $subscriberId = $this->createSubscriber();
        $user         = get_user_by('id', $subscriberId);
        $this->assertInstanceOf(\WP_User::class, $user);
        wp_set_current_user($subscriberId);

        $response = $this->requestProfileUpdate(
            [
                'first_name' => 'Updated',
                'last_name'  => 'Subscriber',
                'email'      => $user->user_email,
            ]
        );

        $this->assertTrue($response['success']);
        $refreshed = get_user_by('id', $subscriberId);
        $this->assertInstanceOf(\WP_User::class, $refreshed);
        $this->assertSame('Updated', $refreshed->first_name);
    }

    private function enableAdminScreen(): void
    {
        if (! class_exists(\WP_Screen::class, false)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
        }

        if (! function_exists('set_current_screen')) {
            require_once ABSPATH . 'wp-admin/includes/screen.php';
        }

        $this->previousScreen = function_exists('get_current_screen') ? get_current_screen() : null;
        set_current_screen('edit-' . ppcart_live_post_type('order'));
    }

    /**
     * @return int
     */
    private function createSubscriber(): int
    {
        $userId = $this->factory()->user->create([ 'role' => 'subscriber' ]);
        $this->assertIsInt($userId);
        $this->createdUserIds[] = (int) $userId;

        return (int) $userId;
    }

    /**
     * @param int    $authorId   Post author user id.
     * @param string $firstName  Stored first name.
     * @return int
     */
    private function createLoggedOrder(int $authorId, string $firstName): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Capability Order',
                'post_author' => $authorId,
            ]
        );
        $this->assertIsInt($orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'status', 'paid');
        ppcart_update_post_meta($orderId, 'firstname', $firstName);
        ppcart_update_post_meta($orderId, 'lastname', 'Buyer');
        ppcart_update_post_meta($orderId, 'email', 'buyer@example.test');
        ppcart_log_entry($orderId, 'IT-365 seed log');

        return (int) $orderId;
    }

    /**
     * @param int    $orderId
     * @param string $firstName
     * @return void
     */
    private function submitOrderSave(int $orderId, string $firstName): void
    {
        $_POST = [
            '_wpnonce'           => wp_create_nonce('update-post_' . $orderId),
            'original_publish'   => 'Update',
            '_ppcart_firstname'  => $firstName,
            '_ppcart_lastname'   => 'Buyer',
            '_ppcart_email'      => 'buyer@example.test',
            '_ppcart_status'     => 'paid',
        ];

        $post = get_post($orderId);
        $this->assertInstanceOf(\WP_Post::class, $post);

        $admin = new PPCart_Order_Admin('ppcart', '1.0.0', 'ppcart');
        $admin->save_post_order($orderId, $post);

        $_POST = [];
    }

    /**
     * @param int $authorId
     * @return array{order_id: int, download_id: int}
     */
    private function createPaidOrderWithDownload(int $authorId): array
    {
        global $wpdb;

        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Download Order',
                'post_author' => $authorId,
            ]
        );
        $this->assertIsInt($orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'status', 'paid');
        ppcart_update_post_meta($orderId, 'email', 'buyer@example.test');

        $productId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'Download Product',
            ]
        );
        $this->assertIsInt($productId);
        $this->createdPostIds[] = (int) $productId;

        ppcart_update_post_meta($orderId, 'product_id', $productId);

        $files = new PPCart_Files();
        $files->setup_download_table();
        $inserted = $wpdb->insert(
            PPCart_Files::live_table(),
            [
                'file_id'             => 'f1',
                'order_id'            => $orderId,
                'order_key'           => 'capkey' . $orderId,
                'product_id'          => $productId,
                'downloads_remaining' => 'unlimited',
            ]
        );
        $this->assertNotFalse($inserted);
        $downloadId = (int) $wpdb->insert_id;
        $this->createdDownloadIds[] = $downloadId;

        return [
            'order_id'     => (int) $orderId,
            'download_id'  => $downloadId,
        ];
    }

    /**
     * @param int    $orderId
     * @param int    $downloadId
     * @param string $remaining
     * @return void
     */
    private function submitDownloadSave(int $orderId, int $downloadId, string $remaining): void
    {
        $_POST = [
            '_wpnonce'                 => wp_create_nonce('update-post_' . $orderId),
            'original_publish'         => 'Update',
            'ppcart_process_downloads' => '1',
            'remaining'                => [
                $downloadId => $remaining,
            ],
        ];

        $post = get_post($orderId);
        $this->assertInstanceOf(\WP_Post::class, $post);

        $files = new PPCart_Files();
        $files->update_order_downloads($orderId, $post);

        $_POST = [];
    }

    /**
     * @param array<string, string> $fields
     * @return array<string, mixed>
     */
    private function requestProfileUpdate(array $fields): array
    {
        $_POST = [
            'nonce'     => wp_create_nonce('ppcart_ajax_nonce'),
            'form_data' => http_build_query($fields),
        ];

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);

        ob_start();
        try {
            if (! function_exists('ppcart_update_user_profile')) {
                require_once PPCART_PLUGIN_ROOT . 'includes/functions/admin-ajax-and-notices.php';
            }
            ppcart_update_user_profile();
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        } finally {
            remove_filter('wp_doing_ajax', '__return_true');
            remove_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);
            $_POST = [];
        }

        $json = trim((string) ob_get_clean());
        $response = json_decode($json, true);
        $this->assertIsArray($response);

        return $response;
    }

    /**
     * @return callable
     */
    public function getAjaxDieHandler(): callable
    {
        return static function ($message = '', $title = '', $args = []): void {
            throw new \RuntimeException('wp_die');
        };
    }
}
