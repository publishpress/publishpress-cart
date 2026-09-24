<?php

if (! defined('ABSPATH')) {
    exit;
}


$sanitized = '';

/**
 * Add additional santization before the default sanitization
 */
do_action('ppcart_pre_sanitize', $sanitized);

switch ($this->type) {
    case 'color':
    case 'radio':
    case 'select':
        $sanitized = $this->sanitize_random($this->data);
        break;

    case 'date':
    case 'datetime':
    case 'datetime-local':
        $sanitized = sanitize_text_field($this->data);
        break;
    case 'time':
    case 'week':
        $sanitized = strtotime($this->data);
        break;

    case 'price':
        $sanitized = $this->format_price();
        break;
    case 'number':
        $sanitized = intval($this->data);
        break;
    case 'range':
        $sanitized = intval($this->data);
        break;

    case 'hidden':
        $sanitized = sanitize_text_field($this->data);
        break;
    case 'month':
    case 'text':
        $sanitized = sanitize_text_field($this->data);
        break;

    case 'checkbox':
        $sanitized = (isset($this->data) ? 1 : 0);
        break;
    case 'html':
        $sanitized = wp_kses_post($this->data);
        break;
    case 'email_editor':
        $sanitized = function_exists('ppcart_kses_email_html') ? ppcart_kses_email_html($this->data) : wp_kses_post($this->data);
        break;
    case 'editor':
        $sanitized = wp_kses_post($this->data);
        break;
    case 'email':
        $sanitized = sanitize_email($this->data);
        break;
    case 'file':
        $sanitized = sanitize_file_name($this->data);
        break;
    case 'tel':
        $sanitized = $this->sanitize_phone($this->data);
        break;
    case 'textarea':
        $sanitized = esc_textarea($this->data);
        break;
    case 'file-upload':
        $sanitized = esc_url($this->data);
        break;
    case 'secure-file-upload':
        $sanitized = esc_url($this->data);
        break;
    case 'url':
        $sanitized = esc_url($this->data);
        break;
} // switch

/**
 * Add additional santization after the default .
 */
do_action('ppcart_post_sanitize', $sanitized);

return $sanitized;
