<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['description'] = '';
$defaults['settings']    = [
    'wpautop'       => true,
    'textarea_name' => $args['id'],
    'textarea_rows' => 20,
    'media_buttons' => false,
    'editor_css'    => '',
    'editor_class'  => '',
    'teeny'         => true,
];
$defaults['value']       = '';
apply_filters($this->plugin_name . '-field-editor-options-defaults', $defaults);
$atts         = wp_parse_args($args, $defaults);
$atts['name'] = $atts['id'];

if (! empty($atts['email_template_key']) && ! empty($atts['email_template_field']) && function_exists('ppcart_get_email_template_value')) {
    $atts['value'] = ppcart_get_email_template_value($atts['email_template_key'], $atts['email_template_field']);
} elseif (get_option($atts['id']) != '') {
    $atts['value'] = get_option($atts['id']);
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-editor.php');
