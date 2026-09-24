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
    wp_send_json_error([ 'message' => __('You do not have permission to send this test email.', 'publishpress-cart') ]);
    return;
}

if (! function_exists('ppcart_get_email_preview_order_data') || ! function_exists('ppcart_send_product_notification')) {
    wp_send_json_error([ 'message' => __('Test email could not be sent.', 'publishpress-cart') ]);
    return;
}

$recipient = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';
if ('' === $recipient) {
    $authenticated_user = wp_get_current_user();
    $recipient          = ($authenticated_user && $authenticated_user->exists()) ? sanitize_email($authenticated_user->user_email) : '';
}

if ('' === $recipient || ! is_email($recipient)) {
    wp_send_json_error([ 'message' => __('Please enter a valid email address to send the test to.', 'publishpress-cart') ]);
    return;
}

$entry = $this->read_notification_post_entry();

// A test send must not silently copy the configured Bcc recipient.
$entry['bcc'] = '';

/* translators: %s: email subject. */
$entry['subject'] = sprintf(__('[Test] %s', 'publishpress-cart'), $entry['subject']);

$order_info = $this->get_product_notification_preview_order_data($product_id);

$sent = ppcart_send_product_notification($entry, $order_info, $recipient);

if ($sent) {
    wp_send_json_success([
        /* translators: %s: recipient email address. */
        'message' => sprintf(__('Test email sent to %s.', 'publishpress-cart'), $recipient),
    ]);
    return;
}

wp_send_json_error([ 'message' => __('Test email failed to send. Check your email/SMTP settings and try again.', 'publishpress-cart') ]);
// phpcs:enable WordPress.Security.NonceVerification.Missing
