<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;
$options = '<option value="">' . esc_html__('Select Payment Plan', 'publishpress-cart') . '</option>';

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately below.
$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
    esc_html_e('error', 'publishpress-cart');
    die();
}
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

if (!isset($_POST['productId']) || empty($_POST['productId'])) {
    esc_html_e('error', 'publishpress-cart');
    wp_die();
}

$ppcart_product_id = absint(wp_unslash($_POST['productId']));
if (
    ! current_user_can('manage_options')
    && ! ppcart_user_can('manage_orders')
    && ! current_user_can('edit_post', $ppcart_product_id)
) {
    wp_send_json_error(esc_html__('You do not have permission to perform this action.', 'publishpress-cart'), 403);
}

//GET PRODUCT PAYMENT OPTIONS get_post_meta
$items = ppcart_get_post_meta($ppcart_product_id, 'pay_options');
if ($items) :
    foreach ($items as $pay_options) {
        foreach ($pay_options as $value) {
            $item_id  = $value['option_id'];
            $item_name  = $value['option_name'] ?? $item_id;
            $sale_item_name    = $value['sale_option_name'] ?? $value['option_name'] . ' (on sale)';
            $options .= '<option value="' . esc_attr($item_id) . '">' . esc_html($item_name) . '</option>';

            if (isset($value['sale_price'])) {
                $options .= '<option value="' . esc_attr($item_id . '_sale') . '">' . esc_html($sale_item_name) . '</option>';
            }
        }
    }
endif;

wp_send_json_success($options);
wp_die();
// phpcs:enable WordPress.Security.NonceVerification.Missing
