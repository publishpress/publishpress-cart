<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']       = 'regular-text';
$defaults['description'] = '';
$defaults['label']       = '';
$defaults['name']        = $args['id'];
$defaults['placeholder'] = '';
$defaults['type']        = 'password';
$defaults['value']       = '';
apply_filters($this->plugin_name . '-field-text-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);

if ($option_val = ppcart_get_sensitive_option($atts['id'])) {
    $atts['value'] = $option_val;
}

$this->apply_secret_field_mask($atts);

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-text.php');
