<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Files_Admin_Trait
{
    public function revoke_notice()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-admin-revoke-notice.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function maybe_revoke_access()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-admin-maybe-revoke-access.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function product_form_callback($post)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-admin-product-form-callback.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update_order_downloads($post_id, $post)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-admin-update-order-downloads.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
