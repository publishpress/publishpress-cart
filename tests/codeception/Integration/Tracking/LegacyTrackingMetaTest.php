<?php

declare(strict_types=1);

namespace Tests\Integration\Tracking;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Legacy_Tracking_Notice_Controller;
use PPCart_Admin_Screens;
use PPCart_Product_Metaboxes;
use PPCart_Public_Asset_Controller;
use ReflectionMethod;

class LegacyTrackingMetaTest extends WPTestCase
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
     * @var int
     */
    private $previousUserId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousUserId = (int) get_current_user_id();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        PPCart_Admin_Screens::reset_cache();
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
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        unset($GLOBALS['ppcart_product']);
        PPCart_Admin_Screens::reset_cache();

        parent::tearDown();
    }

    /**
     * @test-id IT-364
     */
    public function test_IT_364_legacy_tracking_meta_is_not_executed_or_saved(): void
    {
        $marker = 'PPCART_IT364_LEGACY_SNIPPET';
        $productId = $this->createProductWithLegacyTracking($marker);

        $product = (object) [
            'ID' => $productId,
            'currency' => 'USD',
            'price' => '10.00',
            'tracking_main' => $marker,
            'tracking_lead' => $marker,
        ];
        $GLOBALS['ppcart_product'] = $product;

        wp_register_script('ppcart', false, [], '1.0', true);
        $controller = new PPCart_Public_Asset_Controller('ppcart', '1.0', 'ppcart_');
        $controller->js_order_tracking();

        $after = wp_scripts()->get_data('ppcart', 'after');
        $script = is_array($after) ? implode("\n", $after) : (string) $after;
        $this->assertNotSame('', $script);
        $this->assertStringContainsString("jQuery('document').ready", $script);
        $this->assertStringNotContainsString($marker, $script);

        $adminId = $this->createAdministrator();
        wp_set_current_user($adminId);
        add_filter('ppcart_process_stripe_products', '__return_false');

        $_POST['ppcart_fields_nonce'] = wp_create_nonce('ppcart');
        $_POST['_ppcart_on_sale'] = '1';
        $_POST['_ppcart_tracking_main'] = 'alert("pwned")';
        $_POST['_ppcart_tracking_lead'] = 'alert("pwned-lead")';

        $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
        $fieldsMethod = new ReflectionMethod(PPCart_Product_Metaboxes::class, 'get_metabox_fields');
        $fieldsMethod->setAccessible(true);
        $fieldIds = array_column($fieldsMethod->invoke($metaboxes), 0);
        $this->assertNotContains('_ppcart_tracking_main', $fieldIds);
        $this->assertNotContains('_ppcart_tracking_lead', $fieldIds);

        $metaboxes->validate_meta($productId, get_post($productId));
        $this->assertSame('1', (string) get_post_meta($productId, '_ppcart_on_sale', true));
        $this->assertSame($marker, get_post_meta($productId, '_ppcart_tracking_main', true));
        $this->assertSame($marker, get_post_meta($productId, '_ppcart_tracking_lead', true));

        remove_filter('ppcart_process_stripe_products', '__return_false');
    }

    /**
     * @test-id IT-364
     */
    public function test_IT_364_legacy_tracking_notice_renders_then_stays_hidden_once_dismissed(): void
    {
        $title = 'IT-364 Legacy Tracking Product';
        $productId = $this->createProductWithLegacyTracking('console.log("legacy")', $title);

        $adminId = $this->createAdministrator();
        wp_set_current_user($adminId);
        $_GET['page'] = PPCart_Admin_Screens::PAGE_SETTINGS;
        set_current_screen(PPCart_Admin_Screens::HOOK_SETTINGS);
        PPCart_Admin_Screens::reset_cache();

        $controller = new PPCart_Admin_Legacy_Tracking_Notice_Controller();
        ob_start();
        $controller->render_notice();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Studiocart custom tracking scripts are not available', $html);
        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString((string) $productId, $html);
        $this->assertStringNotContainsString('console.log("legacy")', $html);

        $subscriberId = (int) wp_insert_user(
            [
                'user_login' => 'it364_sub_' . wp_generate_password(8, false),
                'user_email' => 'it364_sub_' . wp_generate_password(8, false) . '@example.test',
                'user_pass' => wp_generate_password(12, false),
                'role' => 'subscriber',
            ]
        );
        $this->assertGreaterThan(0, $subscriberId);
        $this->createdUserIds[] = $subscriberId;
        wp_set_current_user($subscriberId);
        ob_start();
        $controller->render_notice();
        $this->assertSame('', (string) ob_get_clean());

        wp_set_current_user($adminId);
        update_user_meta($adminId, '_ppcart_dismiss_admin_notice_legacy_tracking', '1');
        ob_start();
        $controller->render_notice();
        $this->assertSame('', (string) ob_get_clean());
    }

    /**
     * @param string $snippet
     * @param string $title
     * @return int
     */
    private function createProductWithLegacyTracking(string $snippet, string $title = 'Legacy Tracking Product'): int
    {
        $productId = wp_insert_post(
            [
                'post_type' => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title' => $title,
            ]
        );
        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        $this->createdPostIds[] = $productId;

        update_post_meta($productId, '_ppcart_tracking_main', $snippet);
        update_post_meta($productId, '_ppcart_tracking_lead', $snippet);

        return $productId;
    }

    /**
     * @return int
     */
    private function createAdministrator(): int
    {
        $userId = (int) wp_insert_user(
            [
                'user_login' => 'it364_admin_' . wp_generate_password(8, false),
                'user_email' => 'it364_' . wp_generate_password(8, false) . '@example.test',
                'user_pass' => wp_generate_password(12, false),
                'role' => 'administrator',
            ]
        );
        $this->assertGreaterThan(0, $userId);
        $this->createdUserIds[] = $userId;

        return $userId;
    }
}
