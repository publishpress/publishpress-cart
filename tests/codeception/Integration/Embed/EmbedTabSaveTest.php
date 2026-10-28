<?php

declare(strict_types=1);

namespace Tests\Integration\Embed;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Product_Metaboxes;

class EmbedTabSaveTest extends WPTestCase
{
    /**
     * @var int
     */
    private $productId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_POST = [];
        wp_set_current_user($this->factory()->user->create([ 'role' => 'administrator' ]));
        add_filter('ppcart_process_stripe_products', '__return_false');

        $this->productId = (int) $this->factory()->post->create(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'Embed tab save product',
            ]
        );
    }

    protected function tearDown(): void
    {
        $_POST = [];
        remove_filter('ppcart_process_stripe_products', '__return_false');
        wp_set_current_user(0);

        parent::tearDown();
    }

    /**
     * @test-id IT-400
     */
    public function test_IT_400_the_embed_tab_saves_its_seven_fields_through_the_product_metabox(): void
    {
        $this->saveEmbedTab(
            [
                '_ppcart_embed_accent'      => '#FF5A1F',
                '_ppcart_embed_text'        => '#1d2939',
                '_ppcart_embed_background'  => '#fafafa',
                '_ppcart_embed_font_family' => '"Helvetica Neue", Arial, sans-serif',
                '_ppcart_embed_font_url'    => 'https://fonts.googleapis.com/css2?family=Inter&display=swap',
                '_ppcart_embed_radius'      => '12',
                '_ppcart_embed_button_text' => 'Get the course',
            ]
        );

        $this->assertSame(
            [
                'layout'      => '',
                'accent'      => '#ff5a1f',
                'text'        => '#1d2939',
                'background'  => '#fafafa',
                'font_family' => '"Helvetica Neue", Arial, sans-serif',
                'font_url'    => 'https://fonts.googleapis.com/css2?family=Inter&display=swap',
                'radius'      => 12,
                'button_text' => 'Get the course',
            ],
            ppcart_embed_appearance($this->productId)
        );
    }

    /**
     * @test-id IT-401
     */
    public function test_IT_401_an_invalid_saved_accent_reads_back_as_the_default(): void
    {
        $this->saveEmbedTab(
            [
                '_ppcart_embed_accent' => 'red;}',
                '_ppcart_embed_text'   => '#1d2939',
            ]
        );

        $appearance = ppcart_embed_appearance($this->productId);

        $this->assertSame('', $appearance['accent']);
        $this->assertSame('#1d2939', $appearance['text']);
    }

    /**
     * @test-id IT-402
     */
    public function test_IT_402_an_empty_embed_field_deletes_its_meta_and_a_zero_radius_stays(): void
    {
        $this->saveEmbedTab(
            [
                '_ppcart_embed_accent' => '#ff5a1f',
                '_ppcart_embed_radius' => '8',
            ]
        );
        $this->assertTrue(metadata_exists('post', $this->productId, '_ppcart_embed_accent'));

        $this->saveEmbedTab(
            [
                '_ppcart_embed_accent' => '',
                '_ppcart_embed_radius' => '0',
            ]
        );

        $this->assertFalse(metadata_exists('post', $this->productId, '_ppcart_embed_accent'));
        $this->assertSame('', ppcart_embed_appearance($this->productId)['accent']);
        $this->assertSame(0, ppcart_embed_appearance($this->productId)['radius']);
    }

    /**
     * Submit the product edit form with only the given fields, the way WordPress
     * passes a slashed $_POST to save_post.
     *
     * @param array<string, string> $fields Field names to posted values.
     */
    private function saveEmbedTab(array $fields): void
    {
        $_POST = wp_slash(array_merge([ 'ppcart_fields_nonce' => wp_create_nonce('ppcart') ], $fields));

        $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
        $metaboxes->validate_meta($this->productId, get_post($this->productId));
    }
}
