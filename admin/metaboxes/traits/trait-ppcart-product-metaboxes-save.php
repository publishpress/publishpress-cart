<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metaboxes_Save_Trait
{
    /**
     * Returns an array of the all the metabox fields and their respective types
     *
     * @since 1.0.0
     * @access public
     * @return      array       Metabox fields and types
     */
    private function get_metabox_fields()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-save-get-metabox-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

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
        $this->meta = [];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
        if (! isset($_GET['post'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value for loading post meta.
        $post_id = absint(wp_unslash($_GET['post']));
        $post    = get_post($post_id);
        if (! $post instanceof WP_Post) {
            return;
        }

        $post_type = (array) apply_filters('ppcart_product_metabox_post_type', ppcart_live_post_type('product'));

        if (! in_array($post->post_type, $post_type, true)) {
            return;
        }

        $custom     = get_post_custom($post->ID);
        $this->meta = is_array($custom) ? $custom : [];
    }

    /**
     * Saves metabox data
     *
     * Repeater handling sanitizes submitted groups and rebuilds a normalized array
     * before persisting values to post meta.
     *
     * @since 1.0.0
     * @access public
     * @param int       $post_id        The post ID
     * @param object        $object         The post object
     * @return  void
     */
    public function validate_meta($post_id, $object)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-save-validate-meta.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Fillter array remove empty value
     *
     *
     * @param array         $value          The arguments for the field
     * @return  array                       return filtered array
     *
     *
     */
    public function remove_repeater_blank($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) :
                if (empty($val)) {
                    unset($value[$key]);
                }
            endforeach;
        }
        return $value;
    }
}
