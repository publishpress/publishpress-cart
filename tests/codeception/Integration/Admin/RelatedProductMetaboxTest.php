<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Order_Controller;
use WP_Post;

class RelatedProductMetaboxTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var mixed
     */
    private $previousMetaBoxes;

    /**
     * @var bool
     */
    private $hadMetaBoxes = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('add_meta_box')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $this->hadMetaBoxes = array_key_exists('wp_meta_boxes', $GLOBALS);
        $this->previousMetaBoxes = $GLOBALS['wp_meta_boxes'] ?? null;
        $GLOBALS['wp_meta_boxes'] = [];
    }

    protected function tearDown(): void
    {
        remove_all_filters('ppcart_related_product_post_types');

        if ($this->hadMetaBoxes) {
            $GLOBALS['wp_meta_boxes'] = $this->previousMetaBoxes;
        } else {
            unset($GLOBALS['wp_meta_boxes']);
        }

        parent::tearDown();
    }

    /**
     * Returns the screens that got the Related Product box for this post.
     *
     * @return string[]
     */
    private function relatedProductScreens(WP_Post $post): array
    {
        $controller = new PPCart_Admin_Order_Controller('publishpress-cart', 'PublishPress Cart', '1.0');
        $controller->add_related_product_metabox($post->post_type, $post);

        $screens = [];
        foreach ($GLOBALS['wp_meta_boxes'] as $screen => $contexts) {
            foreach ($contexts as $priorities) {
                foreach ($priorities as $boxes) {
                    if (! empty($boxes['ppcart-product'])) {
                        $screens[] = $screen;
                    }
                }
            }
        }

        return $screens;
    }

    /**
     * @test-id IT-395
     */
    public function test_IT_395_page_shows_the_related_product_box(): void
    {
        $page = self::factory()->post->create_and_get(['post_type' => 'page']);

        $this->assertSame(['page'], $this->relatedProductScreens($page));
    }

    /**
     * @test-id IT-395
     */
    public function test_IT_395_post_without_a_related_product_has_no_box(): void
    {
        $post = self::factory()->post->create_and_get();

        $this->assertSame([], $this->relatedProductScreens($post));
    }

    /**
     * @test-id IT-395
     */
    public function test_IT_395_post_that_links_a_product_keeps_the_box(): void
    {
        $product_id = self::factory()->post->create(['post_type' => 'ppcart_product']);
        $post = self::factory()->post->create_and_get();
        ppcart_update_post_meta($post->ID, 'related_product', $product_id);

        $this->assertSame(['post'], $this->relatedProductScreens($post));
    }

    /**
     * @test-id IT-395
     */
    public function test_IT_395_filter_adds_the_box_to_another_post_type(): void
    {
        add_filter(
            'ppcart_related_product_post_types',
            static function (array $post_types): array {
                $post_types[] = 'post';
                return $post_types;
            }
        );
        $post = self::factory()->post->create_and_get();

        $this->assertSame(['post'], $this->relatedProductScreens($post));
    }

    /**
     * @test-id IT-395
     */
    public function test_IT_395_box_lists_products_and_selects_the_linked_one(): void
    {
        $product_id = self::factory()->post->create([
            'post_type'  => 'ppcart_product',
            'post_title' => 'Linked product',
        ]);
        $page = self::factory()->post->create_and_get(['post_type' => 'page']);
        ppcart_update_post_meta($page->ID, 'related_product', $product_id);

        $controller = new PPCart_Admin_Order_Controller('publishpress-cart', 'PublishPress Cart', '1.0');
        ob_start();
        $controller->related_product($page);
        $html = (string) ob_get_clean();

        $this->assertMatchesRegularExpression('/<select name="_ppcart_related_product"[^>]*id="ppcart-related-product"/', $html);
        $this->assertStringContainsString('for="ppcart-related-product"', $html);
        $this->assertMatchesRegularExpression('/<option[^>]*value="' . $product_id . '"[^>]*selected[^>]*>Linked product</', $html);
    }

    /**
     * @test-id IT-395
     */
    public function test_IT_395_admin_init_hooks_the_box_onto_add_meta_boxes(): void
    {
        $controller = new PPCart_Admin_Order_Controller('publishpress-cart', 'PublishPress Cart', '1.0');
        $controller->add_metaboxes();
        $callback = [$controller, 'add_related_product_metabox'];

        $this->assertSame(10, has_action('add_meta_boxes', $callback));

        remove_action('add_meta_boxes', $callback, 10);
    }
}
