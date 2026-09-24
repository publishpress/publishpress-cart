<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Order_Metabox_Save_Trait
{
    private function sanitizer($type, $data)
    {

        if (empty($type)) {
            return;
        }
        if (empty($data)) {
            return;
        }

        $return     = '';
        $sanitizer  = new PPCart_Sanitize();

        $sanitizer->set_data($data);
        $sanitizer->set_type($type);

        $return = $sanitizer->clean();

        unset($sanitizer);

        return $return;
    }

    /**
     * Sets the class variable $options
     */
    public function set_meta()
    {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
        if (isset($_GET['post'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value for loading order/subscription meta.
            $post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
            $post = get_post($post_id);

            if (! ppcart_is_order_post_type($post->post_type) && ! ppcart_is_subscription_post_type($post->post_type)) {
                return;
            }

            $this->meta = get_post_custom($post->ID);
        }

        return;
    }

    /**
     * Saves metabox data
     *
     * Handles posted metabox fields and persists sanitized values.
     *
     * @since 1.0.0
     * @access public
     * @param int       $post_id        The post ID
     * @param object        $object         The post object
     * @return  void
     */
    public function validate_meta($post_id, $object)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-save-validate-meta.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
