<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']       = 'regular-text';
$defaults['description'] = '';
$defaults['label']       = '';
$defaults['name']        = $args['id'];
$defaults['placeholder'] = '';
$defaults['type']        = 'email';
$defaults['value']       = '';
apply_filters($this->plugin_name . '-field-text-options-defaults', $defaults);
$atts       = wp_parse_args($args, $defaults);
$option_val = get_option($atts['id']);

if ($option_val !== false) {
    $atts['value'] = $option_val;
}

if ($atts['id'] == '_ppcart_api_key' && ! $atts['value']) {
    $apikey = bin2hex(random_bytes(32));
    update_option('_ppcart_api_key', $apikey);
    $atts['value'] = $apikey;
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-text.php');
