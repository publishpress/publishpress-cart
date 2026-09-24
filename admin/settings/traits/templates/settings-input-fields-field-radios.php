<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']       = '';
$defaults['description'] = '';
$defaults['label']       = '';
$defaults['name']        = $this->plugin_name . '-options[' . $args['id'] . ']';
$defaults['value']       = 0;
apply_filters($this->plugin_name . '-field-radios-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);

if (! empty($this->options[$atts['id']])) {
    $atts['value'] = $this->options[$atts['id']];
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-radios.php');
