<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;

class RelatedProductMetaboxTest extends WPTestCase
{
    protected $tester;

    private function metabox()
    {
        return new class {
            use \PPCart_Admin_Order_Metabox_Trait;
        };
    }

    private function removeProducts(): void
    {
        foreach (get_posts([
            'post_type' => ppcart_query_post_types('product'),
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]) as $product_id) {
            wp_delete_post($product_id, true);
        }
    }

    private function hasBox(string $post_type, int $post_id): bool
    {
        global $wp_meta_boxes;
        $previous = $wp_meta_boxes;
        unset($wp_meta_boxes[$post_type]['side']['default']['ppcart-product']);
        $this->metabox()->add_related_product_metabox($post_type, get_post($post_id));
        $has_box = ! empty($wp_meta_boxes[$post_type]['side']['default']['ppcart-product']);
        $wp_meta_boxes = $previous;

        return $has_box;
    }

    public function testEmptyAndDraftOnlyCatalogsHideTheBox(): void
    {
        $this->removeProducts();
        $page_id = self::factory()->post->create(['post_type' => 'page']);
        $this->assertFalse($this->hasBox('page', $page_id));
        self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'draft']);
        $this->assertFalse($this->hasBox('page', $page_id));
    }

    public function testPublishedProductShowsBoxOnlyOnEligibleContent(): void
    {
        self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
        $page_id = self::factory()->post->create(['post_type' => 'page']);
        $post_id = self::factory()->post->create(['post_type' => 'post']);
        $this->assertTrue($this->hasBox('page', $page_id));
        $this->assertFalse($this->hasBox('post', $post_id));
    }

    public function testExistingLinkRemainsEditableWithoutPublishedProducts(): void
    {
        $this->removeProducts();
        $post_id = self::factory()->post->create(['post_type' => 'post']);
        ppcart_update_post_meta($post_id, 'related_product', 123);
        $this->assertTrue($this->hasBox('post', $post_id));
    }

    public function testRelatedProductChangesAndRemovalCanBeRestored(): void
    {
        if (! function_exists('wp_post_revision_meta_keys')) {
            $this->markTestSkipped('Metadata revisions require WordPress 6.4 or newer.');
        }
        $this->metabox()->register_related_product_meta();
        $page_id = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        ppcart_update_post_meta($page_id, 'related_product', 123);
        $revision_id = wp_save_post_revision($page_id);
        $this->assertIsInt($revision_id);
        $this->assertGreaterThan(0, $revision_id);
        $this->assertSame(123, (int) get_post_meta($revision_id, ppcart_meta_key('related_product'), true));

        ppcart_update_post_meta($page_id, 'related_product', 456);
        $changed_revision = wp_save_post_revision($page_id);
        $this->assertSame(456, (int) get_post_meta($changed_revision, ppcart_meta_key('related_product'), true));
        wp_restore_post_revision($revision_id);
        $this->assertSame(123, (int) ppcart_get_post_meta($page_id, 'related_product', true));

        ppcart_delete_post_meta($page_id, 'related_product');
        $empty_revision = wp_save_post_revision($page_id);
        ppcart_update_post_meta($page_id, 'related_product', 456);
        wp_restore_post_revision($empty_revision);
        $this->assertFalse(metadata_exists('post', $page_id, ppcart_meta_key('related_product')));
        wp_restore_post_revision($revision_id);
        $this->assertSame(123, (int) ppcart_get_post_meta($page_id, 'related_product', true));
    }

    public function testSavingRevisionDoesNotOverwriteParentFromSubmittedForm(): void
    {
        $page_id = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        ppcart_update_post_meta($page_id, 'related_product', 123);
        $revision_id = _wp_put_post_revision($page_id);
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        $old_user = get_current_user_id();
        $old_post = $_POST;
        wp_set_current_user($user_id);
        $_POST = [
            'ppcart_related_product' => wp_create_nonce('ppcart_related_product'),
            '_ppcart_related_product' => '456',
            'post_type' => 'page',
        ];
        try {
            $this->metabox()->save_access_info($revision_id);
            $this->assertSame(123, (int) ppcart_get_post_meta($page_id, 'related_product', true));
        } finally {
            $_POST = $old_post;
            wp_set_current_user($old_user);
        }
    }
}
