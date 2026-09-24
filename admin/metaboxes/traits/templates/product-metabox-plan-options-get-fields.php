<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
if (!isset($_GET['post'])) {
    return;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value from current admin post.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
$options = [];
$default_fields = apply_filters('ppcart_default_fields', [
    ppcart_meta_key('default_fields') => ppcart_get_post_meta($current_post_id, 'default_fields', true),
]);
if ($default_fields) {
    foreach ($default_fields as $key => $field) {
        if (is_array($field) && count($field) > 0) {
            foreach ($field as $k => $f) {
                if (!isset($f['default_field_disabled'])) {
                    $options[$k] = $f['default_field_label'];
                }
            }
        }
    }
}

if ($custom_fields = ppcart_get_post_meta($current_post_id, 'custom_fields', true)) {
    if (! is_array($custom_fields)) {
        return $options;
    }
    foreach ($custom_fields as $field) {
        if (isset($field['field_id'])) {
            $options[$field['field_id']] = $field['field_label'];
        }
    }
}
return $options;
