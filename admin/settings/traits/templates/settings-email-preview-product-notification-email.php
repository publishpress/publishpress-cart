<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately below.
$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
    wp_send_json_error([ 'message' => __('Invalid request.', 'publishpress-cart') ]);
    return;
}
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

$product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;

if (! $product_id || ! ppcart_is_product_post_type(get_post_type($product_id)) || ! current_user_can('edit_post', $product_id)) {
    wp_send_json_error([ 'message' => __('You do not have permission to preview this product notification.', 'publishpress-cart') ]);
    return;
}

if (! function_exists('ppcart_get_email_preview_order_data') || ! function_exists('ppcart_build_product_notification_email')) {
    wp_send_json_error([ 'message' => __('Email preview could not be generated.', 'publishpress-cart') ]);
    return;
}

$entry      = $this->read_notification_post_entry();
$order_info = $this->get_product_notification_preview_order_data($product_id);
$email      = ppcart_build_product_notification_email($entry, $order_info);

// Truthy fallback mirrors how the header is actually built, so the shown From is faithful.
$from_name  = $entry['from_name'] ? $entry['from_name'] : get_bloginfo('name');
$from_email = $entry['from_email'] ? $entry['from_email'] : get_option('admin_email');
$subject    = ('' !== trim(wp_strip_all_tags((string) $email['subject']))) ? $email['subject'] : __('(no subject)', 'publishpress-cart');

wp_send_json_success(
    [
        'html' => $this->render_notification_inbox_preview($from_name, $from_email, (string) $email['to'], $subject, (string) $email['body']),
    ]
);
// phpcs:enable WordPress.Security.NonceVerification.Missing
