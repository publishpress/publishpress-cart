<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/templates/class-ppcart-product-template.php';
require_once __DIR__ . '/lib/class-ppcart-gutenberg-bootstrap.php';

new PPCart_Product_Template();
new PPCart_Gutenberg_Bootstrap(__FILE__);

require_once dirname(__DIR__) . '/layout/class-ppcart-layout-embeds.php';

add_action('init', [PPCart_Layout_Embeds::class, 'register_shortcodes']);
add_action('elementor/widgets/register', [PPCart_Layout_Embeds::class, 'register_widgets']);
