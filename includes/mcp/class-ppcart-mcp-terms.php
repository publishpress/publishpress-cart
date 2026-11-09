<?php

/**
 * Native product category and tag discovery, editing and assignment.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Terms
{
    /**
     * @since 1.1.0
     * @param string $operation Operation name.
     * @return array
     */
    public function schema($operation)
    {
        $fields = ['taxonomy' => ['type' => 'string', 'enum' => ['category', 'tag']]];
        $required = ['taxonomy'];
        if ('list' === $operation) {
            $fields['search'] = ['type' => 'string', 'maxLength' => 200];
            $fields['page'] = ['type' => 'integer', 'minimum' => 1, 'maximum' => 10000];
            $fields['per_page'] = ['type' => 'integer', 'minimum' => 1, 'maximum' => 50];
        } elseif (in_array($operation, ['create', 'update'], true)) {
            $fields['name'] = ['type' => 'string', 'minLength' => 1, 'maxLength' => 200];
            $fields['slug'] = ['type' => 'string', 'maxLength' => 200];
            $fields['description'] = ['type' => 'string', 'maxLength' => 10000];
            $fields['parent'] = ['type' => 'integer', 'minimum' => 0];
            $fields['dry_run'] = ['type' => 'boolean'];
            if ('create' === $operation) {
                $required[] = 'name';
            }
        }
        if (in_array($operation, ['update', 'get-product', 'set-product', 'deletion', 'delete'], true)) {
            $fields['id'] = ['type' => 'integer', 'minimum' => 1];
            $required[] = 'id';
        }
        if (in_array($operation, ['update', 'set-product', 'delete'], true)) {
            $fields['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $required[] = 'revision';
        }
        if ('delete' === $operation) {
            $fields['dry_run'] = ['type' => 'boolean'];
        }
        if ('set-product' === $operation) {
            $fields['term_ids'] = ['type' => 'array', 'maxItems' => 100, 'items' => ['type' => 'integer', 'minimum' => 1]];
            $fields['dry_run'] = ['type' => 'boolean'];
            $required[] = 'term_ids';
        }
        return PPCart_Mcp_Input::schema($fields, $required);
    }

    /**
     * @since 1.1.0
     * @param array $input Validated input.
     * @param string $capability Capability property.
     * @return WP_Taxonomy|WP_Error
     */
    private function taxonomy(array $input, $capability)
    {
        $name = ppcart_live_taxonomy('category' === $input['taxonomy'] ? 'product_cat' : 'product_tag');
        $taxonomy = get_taxonomy($name);
        if (! $taxonomy || ! current_user_can($taxonomy->cap->$capability)) {
            return $this->forbidden();
        }
        return $taxonomy;
    }

    /**
     * @since 1.1.0
     * @param WP_Term $term Native term.
     * @return array
     */
    private function term_data($term)
    {
        $result = ['id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug,
            'description' => $term->description, 'parent' => $term->parent];
        $result['revision'] = hash('sha256', wp_json_encode($result));
        return $result;
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function listing($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('list'));
        if (is_wp_error($input)) {
            return $input;
        }
        $taxonomy = $this->taxonomy($input, 'manage_terms');
        if (is_wp_error($taxonomy)) {
            return $taxonomy;
        }
        $size = $input['per_page'] ?? 20;
        $terms = get_terms(['taxonomy' => $taxonomy->name, 'hide_empty' => false, 'search' => $input['search'] ?? '',
            'number' => $size, 'offset' => (($input['page'] ?? 1) - 1) * $size, 'orderby' => 'term_id', 'order' => 'ASC']);
        if (is_wp_error($terms)) {
            return $terms;
        }
        $result = [];
        foreach ($terms as $term) {
            if (current_user_can('edit_term', $term->term_id)) {
                $result[] = $this->term_data($term);
            }
        }
        return ['taxonomy' => $input['taxonomy'], 'terms' => $result];
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function create($input)
    {
        return $this->save_term($input, true);
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function update($input)
    {
        return $this->save_term($input, false);
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @param bool $create Create or update.
     * @return array|WP_Error
     */
    private function save_term($input, $create)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema($create ? 'create' : 'update'));
        if (is_wp_error($input)) {
            return $input;
        }
        $taxonomy = $this->taxonomy($input, $create ? 'manage_terms' : 'edit_terms');
        if (is_wp_error($taxonomy)) {
            return $taxonomy;
        }
        if (! $create) {
            $term = get_term($input['id'], $taxonomy->name);
            if (! $term || is_wp_error($term) || ! current_user_can('edit_term', $term->term_id)) {
                return $this->forbidden();
            }
            if (! hash_equals($this->term_data($term)['revision'], $input['revision'])) {
                return $this->stale();
            }
        }
        if (! empty($input['parent'])) {
            $parent = get_term($input['parent'], $taxonomy->name);
            if (! $taxonomy->hierarchical || ! $parent || is_wp_error($parent) || ! current_user_can('edit_term', $parent->term_id)) {
                return PPCart_Mcp_Input::error();
            }
        }
        $patch = array_intersect_key($input, array_flip(['name', 'slug', 'description', 'parent']));
        if (! $patch || (isset($patch['name']) && '' === trim(wp_strip_all_tags($patch['name'])))) {
            return PPCart_Mcp_Input::error();
        }
        if (! empty($input['dry_run'])) {
            $preview = [];
            foreach ($patch as $key => $value) {
                $preview[$key] = sanitize_term_field($key, $value, $create ? 0 : $input['id'], $taxonomy->name, 'db');
            }
            return ['dry_run' => true, 'taxonomy' => $input['taxonomy'], 'changes' => $preview];
        }
        $saved = $create ? wp_insert_term($input['name'], $taxonomy->name, $patch)
            : wp_update_term($input['id'], $taxonomy->name, $patch);
        if (is_wp_error($saved)) {
            return $saved;
        }
        $term = get_term($saved['term_id'], $taxonomy->name);
        return $term && ! is_wp_error($term) ? $this->term_data($term)
            : new WP_Error('ppcart_mcp_save_failed', __('The saved term could not be read back.', 'publishpress-cart'));
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function get_product($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('get-product'));
        return is_wp_error($input) ? $input : $this->product_state($input);
    }

    /**
     * @since 1.1.0
     * @param array $input Validated input.
     * @return array|WP_Error
     */
    private function product_state(array $input)
    {
        $taxonomy = $this->taxonomy($input, 'assign_terms');
        if (is_wp_error($taxonomy)) {
            return $taxonomy;
        }
        $post = get_post($input['id']);
        if (! $post || ! in_array($post->post_type, $taxonomy->object_type, true) || ! current_user_can('edit_post', $post->ID) || ! current_user_can('read_post', $post->ID)) {
            return $this->forbidden();
        }
        $ids = wp_get_object_terms($post->ID, $taxonomy->name, ['fields' => 'ids']);
        if (is_wp_error($ids)) {
            return $ids;
        }
        $ids = array_map('intval', $ids);
        sort($ids);
        return ['id' => $post->ID, 'taxonomy' => $input['taxonomy'], 'term_ids' => $ids,
            'revision' => hash('sha256', wp_json_encode($ids))];
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function set_product($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('set-product'));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->product_state($input);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return $this->stale();
        }
        $taxonomy = $this->taxonomy($input, 'assign_terms');
        foreach ($input['term_ids'] as $id) {
            $term = get_term($id, $taxonomy->name);
            if (! $term || is_wp_error($term) || ! current_user_can('assign_term', $id)) {
                return PPCart_Mcp_Input::error();
            }
        }
        $ids = array_values(array_unique($input['term_ids']));
        sort($ids);
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'id' => $input['id'], 'taxonomy' => $input['taxonomy'], 'term_ids' => $ids];
        }
        $result = wp_set_object_terms($input['id'], $ids, $taxonomy->name, false);
        $after = $this->product_state($input);
        if (is_wp_error($result) || is_wp_error($after) || $after['term_ids'] !== $ids) {
            wp_set_object_terms($input['id'], $before['term_ids'], $taxonomy->name, false);
            $restored = $this->product_state($input);
            return new WP_Error('ppcart_mcp_save_failed', ! is_wp_error($restored) && $restored['term_ids'] === $before['term_ids']
                ? __('Category/tag assignment failed. Previous assignments were restored.', 'publishpress-cart')
                : __('Category/tag assignment failed and could not be fully restored. Read current assignments before retrying.', 'publishpress-cart'));
        }
        return $after;
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function deletion($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('deletion'));
        if (is_wp_error($input)) {
            return $input;
        }
        $taxonomy = $this->taxonomy($input, 'delete_terms');
        if (is_wp_error($taxonomy)) {
            return $taxonomy;
        }
        $term = get_term($input['id'], $taxonomy->name);
        if (! $term || is_wp_error($term) || ! current_user_can('delete_term', $term->term_id)) {
            return $this->forbidden();
        }
        if ((int) get_option('default_term_' . $taxonomy->name) === $term->term_id) {
            return new WP_Error('ppcart_mcp_default_term', __('The native default term cannot be deleted.', 'publishpress-cart'));
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded relation snapshot before a native taxonomy deletion.
        $objects = $wpdb->get_col($wpdb->prepare("SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d ORDER BY object_id LIMIT 1001", $term->term_taxonomy_id));
        if ($wpdb->last_error || ! is_array($objects) || count($objects) > 1000) {
            return new WP_Error('ppcart_mcp_term_limit', __('Use native administration for this term deletion.', 'publishpress-cart'));
        }
        foreach ($objects as $id) {
            $post = get_post((int) $id);
            if (
                ! $post || ! in_array($post->post_type, $taxonomy->object_type, true)
                || ! current_user_can('edit_post', (int) $id) || ! current_user_can('read_post', (int) $id)
            ) {
                return $this->forbidden();
            }
        }
        $children = get_terms(['taxonomy' => $taxonomy->name, 'hide_empty' => false, 'parent' => $term->term_id,
            'number' => 1001, 'orderby' => 'term_id', 'order' => 'ASC']);
        if (is_wp_error($children) || count($children) > 1000) {
            return new WP_Error('ppcart_mcp_term_limit', __('Use native administration for this term hierarchy.', 'publishpress-cart'));
        }
        foreach ($children as $child) {
            if (! current_user_can('edit_term', $child->term_id)) {
                return $this->forbidden();
            }
        }
        return ['id' => $term->term_id, 'taxonomy' => $input['taxonomy'], 'name' => $term->name,
            'affected_products' => count($objects), 'reparented_children' => count($children),
            'revision' => hash_hmac('sha256', wp_json_encode([$this->term_data($term), get_term_meta($term->term_id), $objects, $children]), wp_salt('nonce'))];
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function delete($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('delete'));
        if (is_wp_error($input)) {
            return $input;
        }
        $read = ['taxonomy' => $input['taxonomy'], 'id' => $input['id']];
        $before = $this->deletion($read);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($input['revision'], $before['revision'])) {
            return $this->stale();
        }
        if (! empty($input['dry_run'])) {
            return $before + ['dry_run' => true];
        }
        $taxonomy = ppcart_live_taxonomy('category' === $input['taxonomy'] ? 'product_cat' : 'product_tag');
        $result = wp_delete_term($input['id'], $taxonomy);
        if (is_wp_error($result) || ! $result || term_exists($input['id'], $taxonomy)) {
            return new WP_Error('ppcart_mcp_save_failed', __('Native term deletion could not be confirmed. Read current terms before retrying.', 'publishpress-cart'));
        }
        return ['id' => $input['id'], 'taxonomy' => $input['taxonomy'], 'deleted' => true];
    }

    /** @since 1.1.0 @return WP_Error */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage these product categories or tags.', 'publishpress-cart'));
    }

    /** @since 1.1.0 @return WP_Error */
    private function stale()
    {
        return new WP_Error('ppcart_mcp_stale_revision', __('Categories or tags changed. Read them again before saving.', 'publishpress-cart'));
    }
}
