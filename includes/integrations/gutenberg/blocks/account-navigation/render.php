<?php

if (! defined('ABSPATH')) {
    exit;
}

$block_name = 'publishpress-cart/account-navigation';
$attributes = $renderer->prepare_block_attributes($block_name, $attributes);
$output     = $renderer->render_navigation($attributes, $block);

return $renderer->render_account_block_output($block_name, $attributes, $output);
