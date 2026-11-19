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

// Render the email shell directly. email-main.php escapes each value where it
// prints it, so this preview does not echo a pre-built HTML string.
ppcart_helper()->renderTemplate('email/email-main', $atts);
