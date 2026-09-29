<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Activator;
use PPCart_Admin_Order_List_Controller;
use PPCart_Debug_Log_Viewer;
use PPCart_Product_Metaboxes;

/**
 * Admin write paths check the nonce and the capability before they use request data.
 */
class NonceGuardsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(PPCart_Activator::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-activator.php';
        }

        PPCart_Activator::add_cap();

        $_GET     = [];
        $_POST    = [];
        $_REQUEST = [];
        wp_set_current_user(0);
    }

    protected function tearDown(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_REQUEST = [];
        wp_set_current_user(0);

        parent::tearDown();
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_order_title_ignores_posted_name_without_edit_nonce(): void
    {
        $orderId = $this->createOrder();
        wp_set_current_user($this->createUser('administrator'));
        $_POST = [
            '_ppcart_firstname' => 'Mallory',
            '_ppcart_lastname'  => 'Forged',
            'post_ID'           => (string) $orderId,
        ];

        $data = $this->orderListController()->modify_order_details($this->orderPostData());

        $this->assertSame('Original title', $data['post_title']);
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_order_title_ignores_posted_name_without_edit_capability(): void
    {
        $orderId = $this->createOrder();
        wp_set_current_user($this->createUser('subscriber'));
        $_POST = [
            '_ppcart_firstname' => 'Mallory',
            'post_ID'           => (string) $orderId,
            '_wpnonce'          => wp_create_nonce('update-post_' . $orderId),
        ];

        $data = $this->orderListController()->modify_order_details($this->orderPostData());

        $this->assertSame('Original title', $data['post_title']);
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_order_title_uses_posted_name_with_edit_nonce_and_capability(): void
    {
        $orderId = $this->createOrder();
        wp_set_current_user($this->createUser('administrator'));
        $_POST = [
            '_ppcart_firstname' => 'Ada',
            '_ppcart_lastname'  => 'Lovelace',
            'post_ID'           => (string) $orderId,
            '_wpnonce'          => wp_create_nonce('update-post_' . $orderId),
        ];

        $data = $this->orderListController()->modify_order_details($this->orderPostData());

        $this->assertSame(sanitize_title('#' . $orderId . ' Ada Lovelace'), $data['post_title']);
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_product_meta_save_without_nonce_does_not_run(): void
    {
        $productId = $this->createProduct();
        wp_set_current_user($this->createUser('administrator'));

        $this->assertFalse($this->productSaveRan($productId));
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_product_meta_save_without_capability_does_not_run(): void
    {
        $productId = $this->createProduct();
        wp_set_current_user($this->createUser('subscriber'));
        $_POST = [ 'ppcart_fields_nonce' => wp_create_nonce('ppcart') ];

        $this->assertFalse($this->productSaveRan($productId));
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_product_meta_save_with_nonce_and_capability_runs(): void
    {
        $productId = $this->createProduct();
        wp_set_current_user($this->createUser('administrator'));
        $_POST = [ 'ppcart_fields_nonce' => wp_create_nonce('ppcart') ];

        $this->assertTrue($this->productSaveRan($productId));
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_debug_log_page_rejects_user_without_manage_options(): void
    {
        wp_set_current_user($this->createUser('editor'));
        $_GET['ppcart_view_debug_log_nonce'] = wp_create_nonce('ppcart_view_debug_log');
        $_REQUEST = $_GET;

        $this->assertSame('wp_die', $this->renderDebugLogPage()['result']);
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_debug_log_page_rejects_request_without_view_nonce(): void
    {
        wp_set_current_user($this->createUser('administrator'));

        $this->assertSame('wp_die', $this->renderDebugLogPage()['result']);
    }

    /**
     * @test-id IT-388
     */
    public function test_IT_388_debug_log_page_renders_for_admin_with_view_nonce(): void
    {
        wp_set_current_user($this->createUser('administrator'));
        $_GET['ppcart_view_debug_log_nonce'] = wp_create_nonce('ppcart_view_debug_log');
        $_REQUEST = $_GET;

        $render = $this->renderDebugLogPage();

        $this->assertSame('rendered', $render['result']);
        $this->assertNotSame('', $render['output']);
    }

    /**
     * @return callable
     */
    public function getDieHandler(): callable
    {
        return static function ($message = '', $title = '', $args = []): void {
            throw new \RuntimeException('wp_die');
        };
    }

    private function productSaveRan(int $productId): bool
    {
        $before = did_action('ppcart_before_validate_meta');
        add_filter('ppcart_process_stripe_products', '__return_false');

        try {
            $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
            $metaboxes->validate_meta($productId, get_post($productId));
        } finally {
            remove_filter('ppcart_process_stripe_products', '__return_false');
        }

        return did_action('ppcart_before_validate_meta') > $before;
    }

    /**
     * @return array{result: string, output: string}
     */
    private function renderDebugLogPage(): array
    {
        add_filter('wp_die_handler', [ $this, 'getDieHandler' ]);

        $result = 'rendered';
        ob_start();
        try {
            PPCart_Debug_Log_Viewer::render_log_page();
        } catch (\RuntimeException $exception) {
            $result = $exception->getMessage();
        } finally {
            remove_filter('wp_die_handler', [ $this, 'getDieHandler' ]);
        }

        return [
            'result' => $result,
            'output' => trim((string) ob_get_clean()),
        ];
    }

    private function orderListController(): PPCart_Admin_Order_List_Controller
    {
        return new PPCart_Admin_Order_List_Controller('ppcart', 'Cart', 'test');
    }

    /**
     * @return array<string, string>
     */
    private function orderPostData(): array
    {
        return [
            'post_type'  => ppcart_live_post_type('order'),
            'post_title' => 'Original title',
        ];
    }

    private function createOrder(): int
    {
        return (int) $this->factory()->post->create(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Original title',
            ]
        );
    }

    private function createProduct(): int
    {
        return (int) $this->factory()->post->create(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'Guarded product',
            ]
        );
    }

    private function createUser(string $role): int
    {
        return (int) $this->factory()->user->create([ 'role' => $role ]);
    }
}
