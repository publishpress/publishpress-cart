<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['aria']        = '';
$defaults['blank']       = '';
$defaults['class']       = '';
$defaults['context']     = '';
$defaults['description'] = '';
$defaults['label']       = '';
$defaults['name']        = $args['id'];
$defaults['selections']  = [];
$defaults['value']       = '';
apply_filters($this->plugin_name . '-field-select-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);

if ('_ppcart_invoice_format' === $atts['id'] && function_exists('ppcart_get_invoice_format')) {
    $atts['value'] = ppcart_get_invoice_format();
} elseif ($option_val = get_option($atts['id'])) {
    $atts['value'] = $option_val;
}

if (empty($atts['aria']) && ! empty($atts['description'])) {
    $atts['aria'] = $atts['description'];
} elseif (empty($atts['aria']) && ! empty($atts['label'])) {
    $atts['aria'] = $atts['label'];
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-select.php');
