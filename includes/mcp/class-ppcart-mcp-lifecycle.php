<?php

/**
 * Native catalog duplication and deletion, without payment-record mutation.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Lifecycle
{
    private $products;

    /** @since 1.1.0 @param PPCart_Product_Metaboxes $products Native provider. */
    public function __construct($products)
    {
        $this->products = $products;
    }

    /** @since 1.1.0 @param bool $write Mutation. @return array */
    public function schema($write)
    {
        $content = new PPCart_Mcp_Content();
        $fields = ['kind' => ['type' => 'string', 'enum' => array_keys($content->types())],
            'id' => ['type' => 'integer', 'minimum' => 1]];
        $required = ['kind', 'id'];
        if ($write) {
            $fields['operation'] = ['type' => 'string', 'enum' => ['duplicate', 'trash', 'restore', 'delete']];
            $fields['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $fields['dry_run'] = ['type' => 'boolean'];
            $required = array_merge($required, ['operation', 'revision']);
        }
        return PPCart_Mcp_Input::schema($fields, $required);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        $content = new PPCart_Mcp_Content();
        $post = get_post($input['id']);
        if (
            ! $post || $post->post_type !== $content->types()[$input['kind']]
            || ! $content->permitted($input['kind']) || ! current_user_can('edit_post', $post->ID)
            || ! current_user_can('read_post', $post->ID)
        ) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage this catalog record.', 'publishpress-cart'));
        }
        $terms = [];
        foreach (get_object_taxonomies($post->post_type) as $taxonomy) {
            $ids = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'ids']);
            if (is_wp_error($ids)) {
                return $ids;
            }
            sort($ids);
            $terms[$taxonomy] = $ids;
        }
        return ['id' => $post->ID, 'kind' => $input['kind'], 'status' => $post->post_status,
            'revision' => hash_hmac('sha256', wp_json_encode([$post, get_post_meta($post->ID), $terms]), wp_salt('nonce'))];
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function update($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(true));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->read(['kind' => $input['kind'], 'id' => $input['id']]);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('The catalog record changed. Read it again.', 'publishpress-cart'));
        }
        $operation = $input['operation'];
        $post = get_post($input['id']);
        $type = get_post_type_object($post->post_type);
        if ('duplicate' === $operation) {
            if (
                'product' !== $input['kind'] || 'trash' === $post->post_status || ! PPCart_Product_Duplicator::is_enabled()
                || ! current_user_can($type->cap->create_posts) || ! current_user_can('manage_options')
            ) {
                return new WP_Error('ppcart_mcp_forbidden', __('Product duplication is unavailable.', 'publishpress-cart'));
            }
            foreach (get_object_taxonomies($post->post_type, 'objects') as $taxonomy) {
                if (! current_user_can($taxonomy->cap->assign_terms)) {
                    return new WP_Error('ppcart_mcp_forbidden', __('You cannot copy the product taxonomy assignments.', 'publishpress-cart'));
                }
            }
            // Copying integrations must obey the same grant policy as executing them.
            $orders = new PPCart_Mcp_Order_Admin($this->products);
            $check = $orders->integrations($post->ID);
            if (is_wp_error($check)) {
                return $check;
            }
        } elseif (! current_user_can('delete_post', $post->ID)) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot delete this catalog record.', 'publishpress-cart'));
        }
        if (
            ('trash' === $operation && ('trash' === $post->post_status || ! EMPTY_TRASH_DAYS))
            || (in_array($operation, ['restore', 'delete'], true) && 'trash' !== $post->post_status)
        ) {
            return PPCart_Mcp_Input::error();
        }
        // Core normally restores to draft; filters can request the previous publication status.
        if ('restore' === $operation && ! current_user_can($type->cap->publish_posts)) {
            return new WP_Error('ppcart_mcp_forbidden', __('Restoring requires native publication permission.', 'publishpress-cart'));
        }
        if (! empty($input['dry_run'])) {
            return $before + ['dry_run' => true, 'operation' => $operation];
        }
        $current = $this->read(['kind' => $input['kind'], 'id' => $input['id']]);
        if (is_wp_error($current) || ! hash_equals($input['revision'], $current['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('The catalog record changed before the operation.', 'publishpress-cart'));
        }
        if ('duplicate' === $operation) {
            $id = PPCart_Product_Duplicator::duplicate($post->ID);
            return is_wp_error($id) ? $id : $this->read(['kind' => 'product', 'id' => $id]);
        }
        $result = 'trash' === $operation ? wp_trash_post($post->ID)
            : ('restore' === $operation ? wp_untrash_post($post->ID) : wp_delete_post($post->ID, true));
        if (! $result || ('delete' === $operation && get_post($post->ID))) {
            return new WP_Error('ppcart_mcp_save_failed', __('The catalog operation could not be confirmed. Read before retrying.', 'publishpress-cart'));
        }
        if ('delete' === $operation) {
            return ['id' => $post->ID, 'deleted' => true];
        }
        $after = $this->read(['kind' => $input['kind'], 'id' => $post->ID]);
        if (! is_wp_error($after) && (('trash' === $operation) !== ('trash' === $after['status']))) {
            return new WP_Error('ppcart_mcp_save_failed', __('The catalog status could not be confirmed.', 'publishpress-cart'));
        }
        return $after;
    }
}
