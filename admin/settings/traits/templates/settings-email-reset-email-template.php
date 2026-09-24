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

if (! current_user_can('manage_options')) {
    wp_send_json_error([ 'message' => __('You do not have permission to reset this email template.', 'publishpress-cart') ]);
    return;
}

$template_key = isset($_POST['template']) ? sanitize_key(wp_unslash($_POST['template'])) : '';
if (! function_exists('ppcart_reset_email_template') || ! ppcart_reset_email_template($template_key)) {
    wp_send_json_error([ 'message' => __('Email template could not be reset.', 'publishpress-cart') ]);
    return;
}

wp_send_json_success(
    [
        'fields'     => ppcart_get_email_template_values($template_key),
        'customized' => ppcart_is_email_template_customized($template_key),
        'status'     => __('Using default', 'publishpress-cart'),
    ]
);
// phpcs:enable WordPress.Security.NonceVerification.Missing
