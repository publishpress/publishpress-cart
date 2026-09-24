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
    wp_send_json_error([ 'message' => __('You do not have permission to preview this email template.', 'publishpress-cart') ]);
    return;
}

$template_key = isset($_POST['template']) ? sanitize_key(wp_unslash($_POST['template'])) : '';
$templates    = function_exists('ppcart_email_templates') ? ppcart_email_templates() : [];

if (! $template_key || ! isset($templates[ $template_key ])) {
    wp_send_json_error([ 'message' => __('Email template could not be previewed.', 'publishpress-cart') ]);
    return;
}

$headline = isset($_POST['headline']) ? sanitize_text_field(wp_unslash($_POST['headline'])) : ppcart_get_email_template_value($template_key, 'headline');
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Email HTML is sanitized by ppcart_kses_email_html() to preserve allowed markup.
$body     = isset($_POST['body']) ? ppcart_kses_email_html(wp_unslash($_POST['body'])) : ppcart_get_email_template_value($template_key, 'body');

$order_info = ppcart_get_email_preview_order_data();

wp_send_json_success(
    [
        'html' => $this->render_email_preview_html(
            $template_key,
            ppcart_personalize($headline, $order_info),
            ppcart_personalize($body, $order_info, false, true, true),
            $order_info
        ),
    ]
);
// phpcs:enable WordPress.Security.NonceVerification.Missing
