<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Order_Metabox_Edit_Fields_Trait
{
    private function get_order_edit_fields($post = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-edit-fields-get-order-edit-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function is_existing_order_edit($post)
    {
        return $post instanceof WP_Post && ppcart_is_order_post_type($post->post_type) && 'auto-draft' !== $post->post_status;
    }

    private function get_subscription_edit_fields()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-edit-fields-get-subscription-edit-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function metabox_fields($fields, $post_type = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-edit-fields-metabox-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function render_edit_section_before_fields($section_slug, $post_type)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-render-edit-section-before-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function render_edit_section_after_fields($section_slug, $post_type)
    {
        return;
    }

    private function render_subscription_edit_details_section($post)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-render-subscription-edit-details.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_current_edit_order()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only current edit screen lookup.
        $post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
        if (! $post_id || ! ppcart_is_order_post_type(get_post_type($post_id))) {
            return false;
        }

        $order = new PPCart_Order($post_id);
        if (! $order->id) {
            return false;
        }

        return (object) $order->get_data();
    }

    private function get_edit_field_sections($post_type)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-edit-fields-get-edit-field-sections.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
