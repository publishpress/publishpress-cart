<?php

/**
 * Native WordPress content lifecycle for Cart's editable catalog records.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Content
{
    /**
     * @since 1.1.0
     * @return array
     */
    public function types()
    {
        return apply_filters('ppcart_mcp_editable_post_types', ['product' => ppcart_live_post_type('product')]);
    }

    /**
     * @since 1.1.0
     * @param bool $create Create or update.
     * @return array
     */
    public function schema($create)
    {
        $fields = [
            'kind' => ['type' => 'string', 'enum' => array_keys($this->types())],
            'title' => ['type' => 'string', 'maxLength' => 500],
            'content' => ['type' => 'string', 'maxLength' => 100000],
            'excerpt' => ['type' => 'string', 'maxLength' => 10000],
            'slug' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
            'featured_image_id' => ['type' => 'integer', 'minimum' => 0],
            'status' => ['type' => 'string', 'enum' => ['draft', 'pending', 'private', 'publish']],
            'dry_run' => ['type' => 'boolean'],
        ];
        if (! $create) {
            $fields['id'] = ['type' => 'integer', 'minimum' => 1];
            $fields['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
        }
        return PPCart_Mcp_Input::schema($fields, $create ? ['kind', 'title'] : ['kind', 'id', 'revision']);
    }

    /**
     * @since 1.1.0
     * @param string $kind Record kind.
     * @return bool
     */
    public function permitted($kind)
    {
        $types = $this->types();
        $type = isset($types[$kind]) ? get_post_type_object($types[$kind]) : null;
        return $type && current_user_can($type->cap->edit_posts);
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON input.
     * @return array|WP_Error
     */
    public function create($input)
    {
        return $this->save($input, true);
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON input.
     * @return array|WP_Error
     */
    public function update($input)
    {
        return $this->save($input, false);
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON input.
     * @param bool $create Create or update.
     * @return array|WP_Error
     */
    private function save($input, $create)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema($create));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted($input['kind'])) {
            return $this->forbidden();
        }
        $type = get_post_type_object($this->types()[$input['kind']]);
        if ($create && ! current_user_can($type->cap->create_posts)) {
            return $this->forbidden();
        }
        if (isset($input['featured_image_id'])) {
            $image = $input['featured_image_id'];
            if (
                ! post_type_supports($type->name, 'thumbnail') ||
                ($image &&
                (! current_user_can('upload_files') ||
                ! current_user_can('read_post', $image) ||
                ! current_user_can('edit_post', $image) ||
                ! wp_attachment_is_image($image)))
            ) {
                return $this->forbidden();
            }
        }
        if (isset($input['slug']) && '' === sanitize_title($input['slug'])) {
            return PPCart_Mcp_Input::error();
        }
        $data = [];
        if (! $create) {
            $post = get_post($input['id']);
            if (! $post || $post->post_type !== $type->name || ! current_user_can('edit_post', $post->ID)) {
                return $this->forbidden();
            }
            $records = new PPCart_Mcp_Records();
            $before = $records->read(['kind' => $input['kind'], 'id' => $post->ID]);
            if (is_wp_error($before)) {
                return $before;
            }
            if (! hash_equals($before['revision'], $input['revision'])) {
                return new WP_Error('ppcart_mcp_stale_revision', __('The record changed. Read it again before saving.', 'publishpress-cart'));
            }
            $data['ID'] = $post->ID;
        } else {
            $data = ['post_type' => $type->name, 'post_author' => get_current_user_id(), 'post_status' => 'draft'];
        }
        if (
            isset($input['status']) && in_array($input['status'], ['publish', 'private'], true)
            && ! current_user_can($type->cap->publish_posts)
        ) {
            return $this->forbidden();
        }
        foreach (['title', 'content', 'excerpt', 'status'] as $key) {
            if (array_key_exists($key, $input)) {
                $data['post_' . $key] = $input[$key];
            }
        }
        if (isset($input['slug'])) {
            $data['post_name'] = sanitize_title($input['slug']);
        }
        if (isset($data['post_title']) && '' === trim(wp_strip_all_tags($data['post_title']))) {
            return PPCart_Mcp_Input::error();
        }
        if (! $create && count($data) === 1 && ! isset($input['featured_image_id'])) {
            return PPCart_Mcp_Input::error();
        }
        $preview = wp_unslash(sanitize_post(wp_slash($data), 'db'));
        if (! empty($input['dry_run'])) {
            unset($preview['filter']);
            return ['dry_run' => true, 'kind' => $input['kind'], 'post' => $preview] + (isset($input['featured_image_id']) ? ['featured_image_id' => $input['featured_image_id']] : []);
        }
        $id = $create ? wp_insert_post(wp_slash($data), true) : wp_update_post(wp_slash($data), true);
        if (is_wp_error($id)) {
            return new WP_Error('ppcart_mcp_save_failed', __('Cart record could not be saved.', 'publishpress-cart'));
        }
        if (isset($input['featured_image_id'])) {
            if ($input['featured_image_id']) {
                set_post_thumbnail($id, $input['featured_image_id']);
            } else {
                delete_post_thumbnail($id);
            }
            if ((int) get_post_thumbnail_id($id) !== $input['featured_image_id']) {
                return new WP_Error('ppcart_mcp_save_unconfirmed', __('The record was saved but its image could not be confirmed. Read it before retrying.', 'publishpress-cart'));
            }
        }
        $records = new PPCart_Mcp_Records();
        return $records->read(['kind' => $input['kind'], 'id' => $id]);
    }

    /**
     * @since 1.1.0
     * @return WP_Error
     */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot create or edit this Cart record.', 'publishpress-cart'));
    }
}
