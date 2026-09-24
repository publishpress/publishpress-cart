<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/templates/class-ppcart-product-template.php';
require_once __DIR__ . '/lib/class-ppcart-gutenberg-bootstrap.php';

new PPCart_Product_Template();
new PPCart_Gutenberg_Bootstrap(__FILE__);
