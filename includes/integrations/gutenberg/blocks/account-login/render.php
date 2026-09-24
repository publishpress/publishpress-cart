<?php

if (! defined('ABSPATH')) {
    exit;
}

$block_name = 'publishpress-cart/account-login';
$attributes = $renderer->prepare_block_attributes($block_name, $attributes);

if (is_user_logged_in() && ! empty($attributes['hideWhenLoggedIn'])) {
    $output = '';

    if ($renderer->is_editor_preview()) {
        $output = require __DIR__ . '/preview.php';
    }
} else {
    $template_attributes = $renderer->get_login_template_attributes();
    $output              = ppcart_get_template('my-account/forms/login', 'form', $template_attributes);

    if (empty($template_attributes['action']) || 'login' === $template_attributes['action']) {
        $output = $renderer->prepend_login_intro($output, $attributes);
    }
}

return $renderer->render_account_block_output($block_name, $attributes, $output);
