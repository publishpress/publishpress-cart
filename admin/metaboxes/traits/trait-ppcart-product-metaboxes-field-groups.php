<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metaboxes_Field_Groups_Trait
{
    private function set_field_groups($save = false)
    {
        $post_id = null;
        if (! $save) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only use to load metabox configuration for current post.
            $post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : null;
        }

        $this->set_general_field_group($post_id);
        $this->set_sales_field_groups($save, $post_id);
        $this->set_message_field_groups($save);
        $this->set_integration_field_group($save);
    }
}
