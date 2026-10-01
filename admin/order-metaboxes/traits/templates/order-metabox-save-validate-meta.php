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
if (! ppcart_is_subscription_post_type($object->post_type)) {
    return $post_id;
}

// Included only from validate_meta(), which verifies the ppcart_fields_nonce
// nonce and edit_post capability before this template reads any field.
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in validate_meta() before include.

$metas = $this->get_metabox_fields($object->post_type);

$stripe_objects = [];

foreach ($metas as $meta) {
    $new_value = '';

    $name = $meta[0];
    $field_type = $meta[1];

    if ('html' !== $field_type) {
        $posted_value = null;
        if (isset($_POST[$name]) && is_scalar($_POST[$name])) {
            switch ($field_type) {
                case 'email':
                    $posted_value = sanitize_email(wp_unslash($_POST[$name]));
                    break;

                case 'file':
                    $posted_value = sanitize_file_name(wp_unslash($_POST[$name]));
                    break;

                case 'file-upload':
                case 'secure-file-upload':
                case 'url':
                    $posted_value = esc_url_raw(wp_unslash($_POST[$name]));
                    break;

                case 'textarea':
                    $posted_value = sanitize_textarea_field(wp_unslash($_POST[$name]));
                    break;

                case 'editor':
                case 'email_editor':
                    $posted_value = wp_kses_post(wp_unslash($_POST[$name]));
                    break;

                default:
                    $posted_value = sanitize_text_field(wp_unslash($_POST[$name]));
                    break;
            }
        }

        if (null === $posted_value || ('' === $posted_value && '0' !== $posted_value)) {
            delete_post_meta($post_id, $name);
            continue;
        }

        if ('0' === $posted_value) {
            $new_value = 0;
        } else {
            $new_value = $this->sanitizer($field_type, $posted_value);
        }
    }

    update_post_meta($post_id, $name, $new_value);
} // foreach
// phpcs:enable WordPress.Security.NonceVerification.Missing
