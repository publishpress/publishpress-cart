<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']          = 'regular-text';
$defaults['description']    = '';
$defaults['label']          = '';
$defaults['name']           = $args['id'];
$defaults['placeholder']    = '';
$defaults['type']           = $args['type'] ?? 'text';
$defaults['value']          = $args['value'] ?? '';
apply_filters($this->plugin_name . '-field-text-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);
$option_val = get_option($atts['id']);
if (! empty($atts['email_template_key']) && ! empty($atts['email_template_field']) && function_exists('ppcart_get_email_template_value')) {
    $atts['value'] = ppcart_get_email_template_value($atts['email_template_key'], $atts['email_template_field']);
} elseif ($option_val !== false) {
    $atts['value'] = $option_val;
}
// set API key for subsites if missing
if ($atts['id'] == '_ppcart_api_key' && ! $atts['value']) {
    $apikey = bin2hex(random_bytes(32));
    update_option('_ppcart_api_key', $apikey);
    $atts['value'] = $apikey;
}
if (! empty($atts['secret']) || (class_exists('PPCart_Secrets') && PPCart_Secrets::is_sensitive_option($atts['id']))) {
    $this->apply_secret_field_mask($atts);
}
include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . '' . 'ppcart-admin-field-text.php');
