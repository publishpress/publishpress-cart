<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']        = 'regular-text';
$defaults['name']         = $args['id'];
$defaults['label']        = '';
$defaults['label-remove'] = '';
$defaults['label-upload'] = '';
$defaults['field-type']   = 'url';
apply_filters($this->plugin_name . '-field-textarea-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);

if ($option_val = get_option($atts['id'])) {
    $atts['value'] = $option_val;
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-file-upload.php');
