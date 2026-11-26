<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Screens;
use PPCart_Admin_Settings;

/**
 * Cart > Categories and Cart > Tags open the product term lists.
 */
class ProductTaxonomySubmenuTest extends WPTestCase
{
    private const MENU_GLOBALS = [
        'menu',
        'submenu',
        'admin_page_hooks',
        '_registered_pages',
        '_parent_pages',
        'hook_suffix',
        'current_screen',
        'taxnow',
        'typenow',
        'pagenow',
    ];

    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $originalGlobals = [];

    /**
     * @var array<string, bool>
     */
    private $existingGlobals = [];

    /**
     * @var array<string, mixed>
     */
    private $originalRequest = [];

    /**
     * @var array<string, mixed>
     */
    private $originalGet = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::MENU_GLOBALS as $global_name) {
            $this->existingGlobals[ $global_name ] = array_key_exists($global_name, $GLOBALS);
            $this->originalGlobals[ $global_name ] = $GLOBALS[ $global_name ] ?? null;
        }
        foreach ([ 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages' ] as $global_name) {
            $GLOBALS[ $global_name ] = [];
        }
        $this->originalRequest = $_REQUEST;
        $this->originalGet = $_GET;

        wp_set_current_user(self::factory()->user->create([ 'role' => 'administrator' ]));
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->originalRequest;
        $_GET = $this->originalGet;

        foreach (self::MENU_GLOBALS as $global_name) {
            if ($this->existingGlobals[ $global_name ]) {
                $GLOBALS[ $global_name ] = $this->originalGlobals[ $global_name ];
                continue;
            }

            unset($GLOBALS[ $global_name ]);
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-1088
     */
    public function test_IT_1088_categories_and_tags_submenus_list_products_and_stay_highlighted(): void
    {
        $settings = $this->settings();
        $settings->setup_plugin_options_menu();
        $settings->order_cart_submenu();

        $product_type = ppcart_live_post_type('product');
        $items = array_column($GLOBALS['submenu'][ PPCart_Admin_Screens::menu_slug() ], 2, 0);
        $positions = array_flip(array_keys($items));
        $this->assertLessThan($positions['Tags'], $positions['Categories']);
        $this->assertLessThan($positions['Settings'], $positions['Tags']);

        foreach ([ 'Categories' => ppcart_live_taxonomy('product_cat'), 'Tags' => ppcart_live_taxonomy('product_tag') ] as $label => $taxonomy) {
            $slug = $items[ $label ];
            $query = [];
            parse_str((string) wp_parse_url(html_entity_decode($slug), PHP_URL_QUERY), $query);
            $this->assertSame([ 'taxonomy' => $taxonomy, 'post_type' => $product_type ], $query, $label);

            // Open the screen the submenu links to, as WordPress does.
            $_GET = $query;
            $_REQUEST = $query;
            $GLOBALS['hook_suffix'] = 'edit-tags.php';
            $GLOBALS['pagenow'] = 'edit-tags.php';
            set_current_screen();
            $screen = get_current_screen();
            $this->assertSame($product_type, $screen->post_type, $label);

            // WordPress highlights the submenu item whose slug equals the
            // submenu file it sets on this screen (wp-admin/edit-tags.php).
            $this->assertSame("edit-tags.php?taxonomy={$taxonomy}&amp;post_type={$screen->post_type}", $slug, $label);
            $this->assertSame(PPCart_Admin_Screens::menu_slug(), $settings->taxonomy_menu_highlight("edit.php?post_type={$product_type}"), $label);

            // The term count links to the products with that term.
            $term = self::factory()->term->create_and_get([ 'taxonomy' => $taxonomy, 'slug' => 'it-1088-' . strtolower($label) ]);
            $product_id = self::factory()->post->create([ 'post_type' => $product_type ]);
            wp_set_object_terms($product_id, [ $term->term_id ], $taxonomy);
            $term = get_term($term->term_id, $taxonomy);

            $this->assertStringContainsString(
                esc_url(add_query_arg([ $taxonomy => $term->slug, 'post_type' => $product_type ], 'edit.php')),
                $this->termListTable($screen)->column_posts($term),
                $label
            );
        }
    }

    private function settings(): PPCart_Admin_Settings
    {
        $reflection = new \ReflectionClass(PPCart_Admin_Settings::class);
        $settings = $reflection->newInstanceWithoutConstructor();
        foreach ([ 'plugin_name' => 'ppcart', 'plugin_title' => 'PublishPress Cart', 'version' => 'test' ] as $property_name => $value) {
            $property = $reflection->getProperty($property_name);
            $property->setValue($settings, $value);
        }

        return $settings;
    }

    /**
     * @param \WP_Screen $screen
     * @return \WP_Terms_List_Table
     */
    private function termListTable($screen)
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-terms-list-table.php';

        return new \WP_Terms_List_Table([ 'screen' => $screen ]);
    }
}
