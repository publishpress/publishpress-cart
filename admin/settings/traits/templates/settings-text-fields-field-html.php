<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']          = 'regular-text';
$defaults['description']    = '';
$defaults['label']          = '';
apply_filters($this->plugin_name . '-field-text-options-defaults', $defaults);
$atts = wp_parse_args($args, $defaults);
$allowed_html = wp_kses_allowed_html('post');
$allowed_html['select'] = [
    'aria-label'  => true,
    'class'       => true,
    'data-testid' => true,
    'id'          => true,
    'name'        => true,
];
$allowed_html['option'] = [
    'disabled' => true,
    'selected' => true,
    'value'    => true,
];
if (! isset($allowed_html['div']) || ! is_array($allowed_html['div'])) {
    $allowed_html['div'] = [];
}
$allowed_html['div']['data-pp-stripe-connect-mode'] = true;
$allowed_html['div']['hidden'] = true;
if (! isset($allowed_html['span']) || ! is_array($allowed_html['span'])) {
    $allowed_html['span'] = [];
}
$allowed_html['span']['aria-hidden'] = true;
$allowed_html['span']['class'] = true;
if (! isset($allowed_html['a']) || ! is_array($allowed_html['a'])) {
    $allowed_html['a'] = [];
}
$allowed_html['a']['class'] = true;
$allowed_html['a']['id']    = true;
if (class_exists('PPCart_Admin_Stripe_Webhook_Settings')) {
    $allowed_html = PPCart_Admin_Stripe_Webhook_Settings::augment_allowed_html($allowed_html);
}

echo wp_kses($atts['description'], $allowed_html);
