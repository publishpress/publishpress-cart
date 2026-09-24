<?php

if (! defined('ABSPATH')) {
    exit;
}


$values = [
    'status'             => $sub->status,
    'sub_status'         => $mapped['sub_status'],
    'sub_next_bill_date' => $mapped['sub_next_bill_date'],
    'cancel_at'          => $mapped['cancel_at'],
    'cancel_date'        => $sub->cancel_date,
];

foreach ($values as $field => $value) {
    if ('' === $value || null === $value || false === $value || 0 === $value) {
        ppcart_delete_post_meta($sub->id, $field);
    } else {
        ppcart_update_post_meta($sub->id, $field, $value);
    }
}
