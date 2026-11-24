<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Shopping_Cart_Product_Settings
{
    public static function save(int $product_id): void
    {
        $nonce = isset($_POST['ppcart_fields_nonce']) ? sanitize_text_field(wp_unslash($_POST['ppcart_fields_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'ppcart') || ! current_user_can('edit_post', $product_id)) {
            return;
        }
        if (! isset($_POST['_ppcart_shop_general_submitted'])) {
            return;
        }
        $checked = isset($_POST['_ppcart_show_in_shop']) && '1' === sanitize_text_field(wp_unslash($_POST['_ppcart_show_in_shop']));
        update_post_meta($product_id, '_ppcart_show_in_shop', $checked ? 'yes' : 'no');
    }
}
