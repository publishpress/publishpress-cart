<?php

if (! defined('ABSPATH')) {
    exit;
}


$name  = ! empty($context['customer_name']) ? (string) $context['customer_name'] : '';
$email = ! empty($context['customer_email']) ? (string) $context['customer_email'] : '';

if ($name && $email) {
    return $name . ' <' . $email . '>';
}

return $name ? $name : ($email ? $email : '-');
