<?php

if (! defined('ABSPATH')) {
    exit;
}


if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return $post_id;
}
if (! current_user_can('edit_post', $post_id)) {
    return $post_id;
}

$product_metabox_post_types = array_values(
    array_unique(
        array_merge(
            (array) apply_filters('ppcart_product_metabox_post_type', ppcart_live_post_type('product')),
            ppcart_query_post_types('product')
        )
    )
);
if (!in_array($object->post_type, $product_metabox_post_types, true)) {
    return $post_id;
}

if (
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This reads the nonce field for verification.
    ! isset($_POST['ppcart_fields_nonce']) ||
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This validates the nonce field.
    ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ppcart_fields_nonce'])), $this->plugin_name)
) {
    return $post_id;
}

do_action('ppcart_before_validate_meta', $post_id);

$metas = $this->get_metabox_fields();

$stripe_objects = [];

foreach ($metas as $meta) {
    $new_value = '';

    $name = $meta[0];
    $field_type = $meta[1];
    $defaultFields = apply_filters(
        'ppcart_default_fields_ids',
        [ ppcart_meta_key('default_fields') ]
    );

    if (in_array($name, $defaultFields)) {
        $new_value = [];
        if (! isset($_POST[$name]) || ! is_array($_POST[$name])) {
            continue;
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are sanitized field-by-field in the nested loop below.
        foreach (wp_unslash($_POST[$name]) as $key => $fields) {
            if (! is_array($fields)) {
                continue;
            }
            foreach ($fields as $field => $val) {
                $new_value[$key][$field] = $this->sanitizer('text', $val);
            }
        }
        ppcart_update_post_meta($post_id, $name, $new_value);
    } elseif ('repeater' === $field_type && is_array($meta[2])) {
        // Guard against accidental data loss when repeater inputs are not present in the request.
        if (! isset($_POST[$name]) || ! is_array($_POST[$name])) {
            continue;
        }

        $clean = [];
        $keep = [];
        $remove = [];
        $required_key = false;


        foreach ($meta[2] as $field) {
            if (isset($_POST[$name][$field[0]])) {
                $i = 0;

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are sanitized in the inner branches before use.
                foreach (wp_unslash($_POST[$name][$field[0]]) as $k => $data) {
                    if (isset($field[2]) && strpos($field[2], 'required') !== false) {
                        $required_key = $field[0];
                    }

                    if (empty($data) && isset($field[2]) && strpos($field[2], 'required') !== false) {
                        $remove[] = $k;
                    } else {
                        $keep[] = $k;
                    }

                    if ($field[0] == 'conditions') {
                        $field_arr = [];
                        foreach ($data as $subkey => $subdata) {
                            foreach ($subdata as $subk => $subval) {
                                if ($subk === 'hidden') {
                                    continue;
                                }
                                $field_arr[$subk][$subkey] =  $this->sanitizer('text', $subval);
                            }
                        }
                        if (!empty($field_arr)) {
                            $clean[$field[0]][$k] = $field_arr;
                        }
                    } else {
                        if (is_array($data)) {
                            $field_arr = [];
                            foreach ($data as $d) {
                                $field_arr[] = ($d === '0') ? 0 : $this->sanitizer($field[1], $d);
                            }
                            $clean[$field[0]][$k] = $field_arr;
                        } else {
                            $clean[$field[0]][$k] = ($data === '0') ? 0 : $this->sanitizer($field[1], $data);
                        }
                    }

                    $i++;
                } // foreach
            } // if
        } // foreach

        if (empty($clean) || false === $required_key || ! isset($clean[$required_key]) || ! is_array($clean[$required_key])) {
            continue;
        }

        $count      = $this->get_max($clean);
        $new_value  = [];

        for ($i = 0; $i < $count; $i++) {
            if ($clean[$required_key]) {
                $max = count($clean[$required_key]);
                foreach ($clean as $field_name => $field) {
                    if ($i < $max && isset($field[$i])) {
                        $new_value[$i][$field_name] = $field[$i];
                    }
                } // foreach $clean
            }
        } // for

        if (!empty($remove)) {
            foreach ($remove as $r) {
                unset($new_value[$r]);
            }
            $new_value = array_values($new_value);
        }

        $has_enabled_field = false;
        foreach ($meta[2] as $field) {
            if (isset($field[0]) && 'enabled' === $field[0]) {
                $has_enabled_field = true;
                break;
            }
        }
        if ($has_enabled_field) {
            foreach ($new_value as $row_index => $row) {
                if (is_array($row) && ! array_key_exists('enabled', $row)) {
                    $new_value[$row_index]['enabled'] = 0;
                }
            }
        }

        $stripe_objects[$name] = $new_value;
    } elseif ('html' !== $field_type) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $posted_value is sanitized via $this->sanitizer() before persistence.
        $posted_value = isset($_POST[$name]) ? wp_unslash($_POST[$name]) : null;
        if (null === $posted_value || ('' === $posted_value && '0' !== $posted_value)) {
            ppcart_delete_post_meta($post_id, $name);
            continue;
        }

        if ('0' === $posted_value) {
            $new_value = 0;
        } else {
            $new_value = $this->sanitizer($field_type, $posted_value);
        }
    }

    ppcart_update_post_meta($post_id, $name, $new_value);
} // foreach

if (apply_filters('ppcart_process_stripe_products', true)) {
    $stripe_product = new PPCart_Product_Admin();
    $stripe_product->save_stripe_objects($post_id, $stripe_objects);
}
do_action('ppcart_after_validate_meta', $post_id, $stripe_objects);
