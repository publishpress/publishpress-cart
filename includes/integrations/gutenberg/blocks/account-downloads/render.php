<?php

if (! defined('ABSPATH')) {
    exit;
}

$block_name = 'publishpress-cart/account-downloads';
$attributes = $renderer->prepare_block_attributes($block_name, $attributes);

if ($renderer->is_editor_preview()) {
    $output = require __DIR__ . '/preview.php';
} elseif (is_user_logged_in()) {
    $output = $renderer->render_downloads_content();
    $output = $renderer->wrap_downloads_output($output, $attributes);
} else {
    $output = '';
}

$output = $renderer->render_tab_panel('tab-files', $output, $attributes);

return $renderer->render_account_block_output($block_name, $attributes, $output);
