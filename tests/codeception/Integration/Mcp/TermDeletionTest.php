<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class TermDeletionTest extends McpTestCase
{
    public function test_native_deletion_reparents_children_and_preserves_other_taxonomy(): void
    {
        $taxonomy = ppcart_live_taxonomy('product_cat');
        $parent = wp_insert_term('Parent', $taxonomy)['term_id'];
        $term = wp_insert_term('Delete this', $taxonomy, ['parent' => $parent])['term_id'];
        $child = wp_insert_term('Child', $taxonomy, ['parent' => $term])['term_id'];
        $tag_taxonomy = ppcart_live_taxonomy('product_tag');
        $tag = wp_insert_term('Keep tag', $tag_taxonomy)['term_id'];
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        wp_set_object_terms($id, [$term], $taxonomy);
        wp_set_object_terms($id, [$tag], $tag_taxonomy);
        $state = $this->execute('get-product-term-deletion', ['taxonomy' => 'category', 'id' => $term]);
        $this->assertNotWPError($state);
        $this->assertSame(1, $state['affected_products']);
        $this->assertSame(1, $state['reparented_children']);
        $request = ['taxonomy' => 'category', 'id' => $term, 'revision' => $state['revision'], 'dry_run' => true];
        $this->assertNotWPError($this->execute('delete-product-term', $request));
        $this->assertNotNull(get_term($term, $taxonomy));
        $request['dry_run'] = false;
        $this->assertNotWPError($this->execute('delete-product-term', $request));
        $this->assertNull(get_term($term, $taxonomy));
        $this->assertSame($parent, get_term($child, $taxonomy)->parent);
        $this->assertSame([$tag], wp_get_object_terms($id, $tag_taxonomy, ['fields' => 'ids']));
    }

    public function test_scope_changes_and_denied_object_permission_prevent_deletion(): void
    {
        $taxonomy = ppcart_live_taxonomy('product_tag');
        $term = wp_insert_term('Scoped tag', $taxonomy)['term_id'];
        $read = ['taxonomy' => 'tag', 'id' => $term];
        $state = $this->execute('get-product-term-deletion', $read);
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        wp_set_object_terms($id, [$term], $taxonomy);
        $this->assertWPError($this->execute('delete-product-term', $read + ['revision' => $state['revision']]));
        $deny = static function ($caps, $cap, $user, $args) use ($id) {
            return 'edit_post' === $cap && (int) ($args[0] ?? 0) === $id ? ['do_not_allow'] : $caps;
        };
        add_filter('map_meta_cap', $deny, 1000, 4);
        try {
            $this->assertWPError($this->execute('get-product-term-deletion', $read));
            $this->assertNotNull(get_term($term, $taxonomy));
        } finally {
            remove_filter('map_meta_cap', $deny, 1000);
        }
        $state = $this->execute('get-product-term-deletion', $read);
        $this->assertWPError($this->execute('delete-product-term', $read + ['revision' => $state['revision'], 'id_override' => $id]));
        $this->assertNotWPError($this->execute('delete-product-term', $read + ['revision' => $state['revision']]));
    }

    public function test_child_capability_and_native_default_term_are_protected(): void
    {
        $taxonomy = ppcart_live_taxonomy('product_cat');
        $term = wp_insert_term('Protected', $taxonomy)['term_id'];
        update_option('default_term_' . $taxonomy, $term);
        $this->assertWPError($this->execute('get-product-term-deletion', ['taxonomy' => 'category', 'id' => $term]));
        delete_option('default_term_' . $taxonomy);
        $child = wp_insert_term('Protected child', $taxonomy, ['parent' => $term])['term_id'];
        $deny = static function ($caps, $cap, $user, $args) use ($child) {
            return 'edit_term' === $cap && (int) ($args[0] ?? 0) === $child ? ['do_not_allow'] : $caps;
        };
        add_filter('map_meta_cap', $deny, 1000, 4);
        try {
            $this->assertWPError($this->execute('get-product-term-deletion', ['taxonomy' => 'category', 'id' => $term]));
        } finally {
            remove_filter('map_meta_cap', $deny, 1000);
        }
    }
}
