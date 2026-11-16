<?php

/**
 * Request-scoped authorization of writes made by the native Stripe synchronizer.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Sync_Guard
{
    private $ids;
    private $products;

    /** @since 1.1.0 @param array $ids Authorized existing records. @param PPCart_Product_Metaboxes $products Native provider. */
    public function __construct(array $ids, $products)
    {
        $this->ids = array_fill_keys($ids, true);
        $this->products = $products;
    }

    /** @since 1.1.0 @return void */
    public function register()
    {
        add_filter('wp_insert_post_data', [$this, 'post'], 1, 2);
        add_action('wp_after_insert_post', [$this, 'created'], 1, 3);
        foreach (['add', 'update', 'delete'] as $operation) {
            add_filter($operation . '_post_metadata', [$this, 'metadata'], 1, 5);
        }
    }

    /** @since 1.1.0 @return void */
    public function unregister()
    {
        remove_filter('wp_insert_post_data', [$this, 'post'], 1);
        remove_action('wp_after_insert_post', [$this, 'created'], 1);
        foreach (['add', 'update', 'delete'] as $operation) {
            remove_filter($operation . '_post_metadata', [$this, 'metadata'], 1);
        }
    }

    /** @since 1.1.0 @param string $type Post type. @return bool */
    private function financial($type)
    {
        return ppcart_is_order_post_type($type) || in_array($type, ppcart_query_post_types('subscription'), true);
    }

    /** @since 1.1.0 @param array $data Native post data. @param array $postarr Native post input. @return array */
    public function post(array $data, array $postarr)
    {
        if (! $this->financial($data['post_type'])) {
            return $data;
        }
        $type = get_post_type_object($data['post_type']);
        $id = (int) ($postarr['ID'] ?? 0);
        if ($id) {
            $this->check($id);
        } elseif (! $type || ! current_user_can($type->cap->create_posts) || ! current_user_can($type->cap->publish_posts)) {
            throw new RuntimeException('Native sync creation permission denied.');
        }
        return $data;
    }

    /** @since 1.1.0 @param int $id Post. @param WP_Post $post Native post. @param bool $update Existing post. @return void */
    public function created($id, $post, $update)
    {
        if (! $update && $this->financial($post->post_type)) {
            $this->ids[$id] = true;
        }
    }

    /** @since 1.1.0 @param int $id Record. @return void */
    private function check($id)
    {
        if (! isset($this->ids[$id]) || ! current_user_can('edit_post', $id) || ! current_user_can('read_post', $id)) {
            throw new RuntimeException('Native sync record permission denied.');
        }
    }

    /** @since 1.1.0 @param mixed $result Previous filter. @param int $id Record. @param string $key Meta key. @param mixed $value Meta value. @return mixed */
    public function metadata($result, $id, $key, $value)
    {
        if (! $this->financial(get_post_type($id))) {
            return $result;
        }
        $this->check($id);
        if (
            ppcart_meta_key('user_account') === $key && $value
            && (! current_user_can('list_users') || ! current_user_can('edit_user', (int) $value))
        ) {
            throw new RuntimeException('Native sync related user permission denied.');
        }
        if (ppcart_meta_key('product_id') === $key && $value) {
            $product = (int) $value;
            if (! current_user_can('read_post', $product) || ! current_user_can('edit_post', $product)) {
                throw new RuntimeException('Native sync related product permission denied.');
            }
            $check = (new PPCart_Mcp_Order_Admin($this->products))->integrations($product);
            if (is_wp_error($check)) {
                throw new RuntimeException('Native sync integration policy denied.');
            }
        }
        return $result;
    }
}
