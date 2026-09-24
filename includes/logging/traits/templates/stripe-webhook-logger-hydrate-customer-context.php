<?php

if (! defined('ABSPATH')) {
    exit;
}


if (empty($context['customer_email'])) {
    $context['customer_email'] = self::get_record_value($record, $post_id, 'email', 'email');
}

if (empty($context['customer_name'])) {
    $name = self::get_record_value($record, $post_id, 'customer_name', 'customer_name');
    if (! $name) {
        $first = self::get_record_value($record, $post_id, 'first_name', 'first_name');
        if (! $first) {
            $first = self::get_record_value($record, $post_id, 'firstname', 'firstname');
        }

        $last = self::get_record_value($record, $post_id, 'last_name', 'last_name');
        if (! $last) {
            $last = self::get_record_value($record, $post_id, 'lastname', 'lastname');
        }

        $name = trim($first . ' ' . $last);
    }

    if ($name) {
        $context['customer_name'] = $name;
    }
}

return $context;
