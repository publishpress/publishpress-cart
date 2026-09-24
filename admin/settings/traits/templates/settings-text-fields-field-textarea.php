<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']       = 'regular-text';
$defaults['cols']        = 50;
$defaults['context']     = '';
$defaults['description'] = '';
$defaults['label']       = '';
$defaults['name']        = $args['id'];
$defaults['rows']        = 10;
$defaults['value']       = '';
apply_filters($this->plugin_name . '-field-textarea-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);

if (! empty($atts['email_template_key']) && ! empty($atts['email_template_field']) && function_exists('ppcart_get_email_template_value')) {
    $atts['value'] = ppcart_get_email_template_value($atts['email_template_key'], $atts['email_template_field']);
} elseif ($option_val = get_option($atts['id'])) {
    $atts['value'] = $option_val;
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-textarea.php');
