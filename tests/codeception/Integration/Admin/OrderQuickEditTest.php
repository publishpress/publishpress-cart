<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Order_List_Controller;
use ReflectionClass;

class OrderQuickEditTest extends WPTestCase
{
    /**
     * @var int[]
     */
    private $createdPostIds = [];

    /**
     * @var int[]
     */
    private $createdUserIds = [];

    /**
     * @var int
     */
    private $previousUserId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousUserId = (int) get_current_user_id();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }

        $this->createdPostIds = [];

        foreach ($this->createdUserIds as $userId) {
            wp_delete_user($userId);
        }

        $this->createdUserIds = [];
        wp_set_current_user($this->previousUserId);

        parent::tearDown();
    }

    /**
     * @test-id IT-379
     */
    public function test_IT_379_order_number_column_provides_core_quick_edit_data(): void
    {
        if (! function_exists('get_inline_data')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $this->log_in_as_administrator('it379');

        $postId = wp_insert_post(
            [
                'post_type' => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title' => 'Quick Edit Order',
                'post_date' => '2026-09-28 12:57:00',
            ]
        );

        $this->assertIsInt($postId);
        $this->assertGreaterThan(0, $postId);
        $this->createdPostIds[] = $postId;

        $controller = (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))
            ->newInstanceWithoutConstructor();

        ob_start();
        $controller->custom_order_column('order', $postId);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('id="inline_' . $postId . '"', $html);
        $this->assertStringContainsString('<div class="post_title">Quick Edit Order</div>', $html);
        $this->assertStringContainsString('<div class="_status">publish</div>', $html);
        $this->assertStringContainsString('<div class="jj">28</div>', $html);
        $this->assertStringContainsString('<div class="hh">12</div>', $html);
        $this->assertStringContainsString('<div class="mn">57</div>', $html);
    }

    /**
     * @test-id IT-380
     */
    public function test_IT_380_subscription_id_column_provides_core_quick_edit_data(): void
    {
        if (! function_exists('get_inline_data')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $this->log_in_as_administrator('it380');

        $postId = wp_insert_post(
            [
                'post_type' => ppcart_live_post_type('subscription'),
                'post_status' => 'publish',
                'post_title' => 'Quick Edit Subscription',
                'post_date' => '2026-09-29 13:58:00',
            ]
        );

        $this->assertIsInt($postId);
        $this->assertGreaterThan(0, $postId);
        $this->createdPostIds[] = $postId;

        $controller = (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))
            ->newInstanceWithoutConstructor();

        ob_start();
        $controller->custom_subscription_column('sub_id', $postId);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('id="inline_' . $postId . '"', $html);
        $this->assertStringContainsString('<div class="post_title">Quick Edit Subscription</div>', $html);
        $this->assertStringContainsString('<div class="_status">publish</div>', $html);
        $this->assertStringContainsString('<div class="jj">29</div>', $html);
        $this->assertStringContainsString('<div class="hh">13</div>', $html);
        $this->assertStringContainsString('<div class="mn">58</div>', $html);
    }

    /**
     * @param string $prefix Unique test-user prefix.
     * @return void
     */
    private function log_in_as_administrator($prefix): void
    {
        $adminId = (int) wp_insert_user(
            [
                'user_login' => $prefix . '_admin_' . wp_generate_password(8, false),
                'user_email' => $prefix . '_' . wp_generate_password(8, false) . '@example.test',
                'user_pass' => wp_generate_password(12, false),
                'role' => 'administrator',
            ]
        );

        $this->assertGreaterThan(0, $adminId);
        $this->createdUserIds[] = $adminId;
        wp_set_current_user($adminId);
    }
}
