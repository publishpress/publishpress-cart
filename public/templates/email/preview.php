<?php

if (! defined('ABSPATH')) {
    exit;
}


$preview_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (is_string($preview_type) && '' !== $preview_type) {
    preg_match('/\[([a-z_]*?)\]/', $preview_type, $match);
    $email_type = $match[1] ?? 'confirmation';
} else {
    $email_type = 'confirmation';
}

$headline = ppcart_get_email_template_value($email_type, 'headline');
$body = ppcart_get_email_template_value($email_type, 'body');

$order_info = ppcart_get_email_preview_order_data();
$atts = [
    'type' => $email_type,
    'order_info' => $order_info,
    'headline' => $headline,
    'body' => ppcart_personalize($body, $order_info, false, true, true),
];

$body = ppcart_get_email_html($atts);
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Email shell already escapes at each output statement in email-main.php.
echo $body;
