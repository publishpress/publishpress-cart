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
    public function test_IT_482_screen_context_uses_the_edited_post_not_the_global_post(): void
    {
        $eventId = self::factory()->post->create([ 'post_type' => 'it_event', 'post_status' => 'publish' ]);
        $productId = self::factory()->post->create([ 'post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish' ]);
        $productType = ppcart_live_post_type('product');

        // Another plugin's edit screen, with the global $post left on a Cart product.
        $this->openEditScreen($eventId, 'it_event');
        $GLOBALS['post'] = get_post($productId);
        $this->assertFalse(PPCart_Admin_Screens::is_post_type_screen($productType, 'post.php'));
        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen('post.php'));

        // A Cart product's edit screen, with the global $post left on another post.
        $this->openEditScreen($productId, $productType);
        $GLOBALS['post'] = get_post($eventId);
        $this->assertTrue(PPCart_Admin_Screens::is_post_type_screen($productType, 'post.php'));
        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen('post.php'));
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
