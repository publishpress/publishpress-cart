<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']       = 'regular-text';
$defaults['description'] = '';
$defaults['label']       = '';
$defaults['name']        = $args['id'];
$defaults['placeholder'] = '';
$defaults['type']        = 'color';
$defaults['value']       = '';
apply_filters($this->plugin_name . '-field-text-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);

if ($option_val = get_option($atts['id'])) {
    $atts['value'] = $option_val;
}

if (! empty($atts['secret']) || (class_exists('PPCart_Secrets') && PPCart_Secrets::is_sensitive_option($atts['id']))) {
    $this->apply_secret_field_mask($atts);
}

if ($atts['id'] == '_ppcart_api_key' && ! get_option($atts['id'])) {
    $apikey = bin2hex(random_bytes(32));
    update_option('_ppcart_api_key', $apikey);
    $atts['value'] = $apikey;
}

include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-text.php');
