<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']       = '';
$defaults['description'] = '';
$defaults['label']       = '';
$defaults['name']        = $args['id'];
$defaults['value']       = 0;
apply_filters($this->plugin_name . '-field-checkbox-options-defaults', $defaults);
$atts       = wp_parse_args($args, $defaults);
$option_val = get_option($atts['id'], false);

if (false !== $option_val && empty($atts['force_value'])) {
    $atts['value'] = $option_val;
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-checkbox.php');
