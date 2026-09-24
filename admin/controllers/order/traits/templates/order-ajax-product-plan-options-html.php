<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately below.
$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
    esc_html_e('Ooops, something went wrong, please try again later.', 'publishpress-cart');
    die();
}
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

if (!isset($_POST['post_id'])) {
    esc_html_e('Invalid Post ID', 'publishpress-cart');
    die();
}

// Get pay options
$product_id = absint(wp_unslash($_POST['post_id']));
if (
    ! current_user_can('manage_options')
    && ! ppcart_user_can('manage_orders')
    && ! current_user_can('edit_post', $product_id)
) {
    wp_die(esc_html__('You do not have permission to perform this action.', 'publishpress-cart'));
}

$product_plan_data = ppcart_get_post_meta($product_id, 'pay_options', true);

$default = '<option>' . esc_html__('No recurring payment plans found', 'publishpress-cart') . '</option>';

if ($product_plan_data && is_array($product_plan_data)) {
    $options = '';
    $request_type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    foreach ($product_plan_data as $val) {
        if ('recurring' === $request_type) {
            if (!isset($val['product_type']) || $val['product_type'] != 'recurring') {
                continue;
            }
        }
        $label = $val['option_name'] ?? $val['option_id'];
        $options .= '<option value="' . esc_attr($val['option_id']) . '">' . esc_html($label) . '</option>';
    }

    if (!$options) {
        $options = $default;
    }
} else {
    $options = $default;
}

echo wp_kses($options, ['option' => ['value' => true, 'selected' => true, 'id' => true], 'optgroup' => ['label' => true]]);
die();
// phpcs:enable WordPress.Security.NonceVerification.Missing
