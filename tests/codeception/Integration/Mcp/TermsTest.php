<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class TermsTest extends McpTestCase
{
    public function test_category_creation_assignment_and_readback(): void
    {
        $category = $this->execute('create-product-term', ['taxonomy' => 'category', 'name' => 'MCP Category']);
        $this->assertNotWPError($category);
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $tag = self::factory()->term->create(['taxonomy' => ppcart_live_taxonomy('product_tag'), 'name' => 'Preserved tag']);
        wp_set_object_terms($id, [$tag], ppcart_live_taxonomy('product_tag'));
        $before = $this->execute('get-product-terms', ['taxonomy' => 'category', 'id' => $id]);
        $patch = ['taxonomy' => 'category', 'id' => $id, 'revision' => $before['revision'], 'term_ids' => [$category['id']]];
        $preview = $this->execute('set-product-terms', array_merge($patch, ['dry_run' => true]));
        $this->assertNotWPError($preview);
        $this->assertSame([], wp_get_object_terms($id, ppcart_live_taxonomy('product_cat'), ['fields' => 'ids']));
        $saved = $this->execute('set-product-terms', $patch);
        $this->assertNotWPError($saved);
        $this->assertSame([$category['id']], $saved['term_ids']);
        $this->assertSame([$tag], array_map('intval', wp_get_object_terms($id, ppcart_live_taxonomy('product_tag'), ['fields' => 'ids'])));
        $this->assertWPError($this->execute('set-product-terms', $patch));
    }

    public function test_invalid_term_batch_and_denied_capability_do_not_write(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $category = self::factory()->term->create(['taxonomy' => ppcart_live_taxonomy('product_cat')]);
        $before = $this->execute('get-product-terms', ['taxonomy' => 'category', 'id' => $id]);
        $this->assertWPError($this->execute('set-product-terms', [
            'taxonomy' => 'category', 'id' => $id, 'revision' => $before['revision'], 'term_ids' => [$category, 999999999],
        ]));
        $this->assertSame([], wp_get_object_terms($id, ppcart_live_taxonomy('product_cat'), ['fields' => 'ids']));
        $taxonomy = get_taxonomy(ppcart_live_taxonomy('product_cat'));
        wp_get_current_user()->add_cap($taxonomy->cap->manage_terms, false);
        $this->assertWPError($this->execute('create-product-term', ['taxonomy' => 'category', 'name' => 'Denied']));
    }
}
