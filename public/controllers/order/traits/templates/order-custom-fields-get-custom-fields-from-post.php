<?php

if (! defined('ABSPATH')) {
    exit;
}


if (is_int($ppcart_product)) {
    $ppcart_product = ppcart_setup_product($ppcart_product);
}

$custom_fields = [];
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
foreach ($ppcart_product->custom_fields as $field) {
    $key = str_replace([' ','.'], ['_','_'], $field['field_id']);
    if (isset($posted_custom_fields[ $key ]) && '' !== $posted_custom_fields[ $key ] && 'password' !== $field['field_type']) {
        $field_id = sanitize_text_field($field['field_id']);
        if (is_array($posted_custom_fields[ $key ]) && 1 === count($posted_custom_fields[ $key ])) {
            $value = sanitize_text_field($posted_custom_fields[ $key ][0]);
        } elseif (is_array($posted_custom_fields[ $key ])) {
            $value = [];
            foreach ($posted_custom_fields[ $key ] as $val) {
                $value[] = sanitize_text_field($val);
            }
        } else {
            $value = sanitize_text_field($posted_custom_fields[ $key ]);
        }
        $custom_fields[$field_id] = [
            'label' => sanitize_text_field($field['field_label']),
            'value' => $value,
        ];

        if (in_array($field['field_type'], ['select', 'checkbox','radio'])) {
            $choices = [];
            $options = explode("\n", str_replace("\r", "", esc_attr($field['select_options'])));
            for ($i = 0; $i < count($options); $i++) {
                $option = explode(':', $options[$i]);
                if (count($option) > 1) {
                    if (trim($option[0]) == $value) {
                        $label = trim($option[1]);
                        if (is_array($custom_fields[$field_id]['value'])) {
                            if (!isset($custom_fields[$field_id]['value_label'])) {
                                $custom_fields[$field_id]['value_label'] = [];
                            }
                            $custom_fields[$field_id]['value_label'][] = $label;
                        } else {
                            $custom_fields[$field_id]['value_label'] = $label;
                            break;
                        }
                    }
                }
            }
        }
    }
}
return $custom_fields;
