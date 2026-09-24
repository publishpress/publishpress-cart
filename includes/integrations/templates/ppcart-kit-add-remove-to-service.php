<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;
if (empty($int['services']) || empty($int['service_action'])) {
    return;
}

// Get required data from order with safe defaults
$email = $order['email'] ?? '';
$fname = $order['first_name'] ?? '';
$lname = $order['last_name'] ?? '';
$phone = $order['phone'] ?? '';

// Get forms and tags from integration settings
$ppcart_mail_forms = $int['convertkit_forms'] ?? '';
$ppcart_mail_tags = $int['convertkit_tags'] ?? '';

// Get field map if exists
$fieldmap = $int['convertkit_field_map'] ?? '';

// Call the actual implementation

$this->add_remove_convertkit_subscriber(
    $order['id'],
    'convertkit',
    $int['service_action'],
    $ppcart_mail_forms,
    $ppcart_mail_tags,
    $email,
    $phone,
    $fname,
    $lname,
    $fieldmap
);
