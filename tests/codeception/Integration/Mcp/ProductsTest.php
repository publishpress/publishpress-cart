<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ProductsTest extends McpTestCase
{
    public function test_product_preview_save_and_stale_revision(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        ppcart_update_post_meta($id, 'product_name', 'Before');
        ppcart_update_post_meta($id, 'unrelated_test_setting', 'Preserved');
        $before = $this->execute('get-product-settings', ['id' => $id]);
        $this->assertNotWPError($before);
        $patch = [
            'id' => $id,
            'changes' => (object) ['_ppcart_product_name' => "Reader's \"choice\"", '_ppcart_hide_title' => false],
            'revision' => $before['revision'], 'dry_run' => true,
        ];
        $preview = $this->execute('update-product-settings', $patch);
        $this->assertNotWPError($preview);
        $this->assertSame('Before', ppcart_get_post_meta($id, 'product_name', true));
        $patch['dry_run'] = false;
        $saved = $this->execute('update-product-settings', $patch);
        $this->assertNotWPError($saved);
        $this->assertSame("Reader's \"choice\"", ppcart_get_post_meta($id, 'product_name', true));
        $this->assertSame('0', (string) ppcart_get_post_meta($id, 'hide_title', true));
        $this->assertSame('Preserved', ppcart_get_post_meta($id, 'unrelated_test_setting', true));
        $this->assertWPError($this->execute('update-product-settings', $patch));
    }

    public function test_mixed_invalid_product_patch_preserves_every_field(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $before = $this->execute('get-product-settings', ['id' => $id]);
        foreach (['false', 0, null, [], (object) []] as $invalid) {
            $this->assertWPError($this->execute('update-product-settings', [
                'id' => $id, 'revision' => $before['revision'],
                'changes' => (object) ['_ppcart_product_name' => 'Must not save', '_ppcart_hide_title' => $invalid],
            ]));
            $this->assertSame('', ppcart_get_post_meta($id, 'product_name', true));
        }
        $this->assertWPError($this->execute('get-product-settings', ['id' => (string) $id]));
    }

    public function test_denied_object_capability_blocks_administrator(): void
    {
        $other = self::factory()->user->create(['role' => 'administrator']);
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => $other]);
        $type = get_post_type_object(ppcart_live_post_type('product'));
        wp_get_current_user()->add_cap($type->cap->edit_others_posts, false);
        $this->assertWPError($this->execute('get-product-settings', ['id' => $id]));
    }

    public function test_order_read_does_not_expose_bearer_tokens(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_author' => get_current_user_id()]);
        ppcart_update_post_meta($id, 'invoice_token', 'must-never-be-returned');
        ppcart_update_post_meta($id, 'amount', '12.50');
        $read = $this->execute('get-record', ['kind' => 'order', 'id' => $id]);
        $this->assertNotWPError($read);
        $this->assertStringNotContainsString('must-never-be-returned', wp_json_encode($read));
        $this->assertEquals(12.5, $read['amount']);
        wp_get_current_user()->add_cap(get_post_type_object(ppcart_live_post_type('order'))->cap->edit_posts, false);
        $this->assertWPError($this->execute('get-record', ['kind' => 'order', 'id' => $id]));
    }
}
