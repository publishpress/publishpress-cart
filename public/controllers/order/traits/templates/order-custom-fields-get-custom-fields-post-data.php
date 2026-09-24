<?php

if (! defined('ABSPATH')) {
    exit;
}


$custom_fields = false;
$post_data = ppcart_filter_input_array(
    INPUT_POST,
    [
        'ppcart_custom_fields' => [
            'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'flags'  => FILTER_REQUIRE_ARRAY,
        ],
    ]
);
$posted_custom_fields = ppcart_sanitize_request_group(isset($post_data['ppcart_custom_fields']) ? $post_data['ppcart_custom_fields'] : [], 'text', 'text');

if (! empty($posted_custom_fields)) {
    $custom_fields['ppcart_custom_fields'] = [];
    foreach ($posted_custom_fields as $key => $value) {
        $value = sanitize_text_field($value);
        $custom_fields['ppcart_custom_fields'][$key] = $value;
    }
    $custom_fields['ppcart_product_id'] = $pid;
}
return $custom_fields;
