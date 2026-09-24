<?php

if (! defined('ABSPATH')) {
    exit;
}


$log_entries = ppcart_get_post_meta($this->id, 'refund_log', true);

if (!is_array($log_entries)) {
    $log_entries = [];
}

if (class_exists('PPCart_Stripe_Sync')) {
    $log_entries = PPCart_Stripe_Sync::normalize_refund_log($log_entries);
}

$log_key = $refundID;
if ('manual' === $refundID && isset($log_entries[$log_key])) {
    $log_key = 'manual_' . time() . '_' . count($log_entries);
}

$log_entries[$log_key] = [
  'refundID' => $refundID,
  'date' => gmdate('Y-m-d H:i'),
  'amount' => $amount,
];

ppcart_update_post_meta($this->id, 'refund_log', $log_entries);

$this->refund_log = $log_entries;
