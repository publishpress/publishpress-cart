<?php

if (! defined('ABSPATH')) {
    exit;
}

$block_name = 'publishpress-cart/account-profile';
$attributes = $renderer->prepare_block_attributes($block_name, $attributes);
$tab_id     = 'tab-profile';

if ($renderer->should_render_tab_block_preview($tab_id)) {
    $output = require __DIR__ . '/preview.php';
} elseif (is_user_logged_in()) {
    $output = $renderer->render_tab_block_content($tab_id, 'user-profile', $attributes);
    $output = $renderer->apply_tab_block_attributes($tab_id, $output, $attributes);
} else {
    $output = '';
}

$output = $renderer->render_tab_panel($tab_id, $output, $attributes);

return $renderer->render_account_block_output($block_name, $attributes, $output);
