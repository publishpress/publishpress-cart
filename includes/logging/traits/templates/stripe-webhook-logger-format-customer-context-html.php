<?php

if (! defined('ABSPATH')) {
    exit;
}


$name  = ! empty($context['customer_name']) ? (string) $context['customer_name'] : '';
$email = ! empty($context['customer_email']) ? (string) $context['customer_email'] : '';

if ($name && $email) {
    return esc_html($name) . '<br><small>' . esc_html($email) . '</small>';
}

if ($name) {
    return esc_html($name);
}

if ($email) {
    return '<small>' . esc_html($email) . '</small>';
}

return '-';
