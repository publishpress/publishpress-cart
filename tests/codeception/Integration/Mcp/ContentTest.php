<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ContentTest extends McpTestCase
{
    public function test_create_preview_and_content_update_preserve_product_metadata(): void
    {
        $request = ['kind' => 'product', 'title' => 'MCP draft', 'content' => 'Initial content', 'dry_run' => true];
        $preview = $this->execute('create-record', $request);
        $this->assertNotWPError($preview);
        $this->assertTrue($preview['dry_run']);
        $request['dry_run'] = false;
        $created = $this->execute('create-record', $request);
        $this->assertNotWPError($created);
        $id = $created['id'];
        $this->assertSame('draft', get_post_status($id));
        ppcart_update_post_meta($id, 'product_name', 'Preserved product name');
        $saved = $this->execute('update-record', [
            'kind' => 'product', 'id' => $id, 'revision' => $created['revision'], 'content' => 'Updated content',
        ]);
        $this->assertNotWPError($saved);
        $this->assertSame('Updated content', get_post_field('post_content', $id));
        $this->assertSame('MCP draft', get_post_field('post_title', $id));
        $this->assertSame('Preserved product name', ppcart_get_post_meta($id, 'product_name', true));
        $this->assertWPError($this->execute('update-record', [
            'kind' => 'product', 'id' => $id, 'revision' => $created['revision'], 'content' => 'Stale update',
        ]));
    }

    public function test_publish_and_unknown_fields_do_not_bypass_permissions(): void
    {
        $type = get_post_type_object(ppcart_live_post_type('product'));
        wp_get_current_user()->add_cap($type->cap->publish_posts, false);
        $this->assertWPError($this->execute('create-record', ['kind' => 'product', 'title' => 'Denied', 'status' => 'publish']));
        $this->assertWPError($this->execute('create-record', ['kind' => 'product', 'title' => 'Denied', 'post_author' => 1]));
        $this->assertWPError($this->execute('create-record', ['kind' => 'order', 'title' => 'Fake payment']));
        $draft = $this->execute('create-record', ['kind' => 'product', 'title' => 'Allowed draft']);
        $this->assertNotWPError($draft);
        $this->assertSame('draft', get_post_status($draft['id']));
    }
}
