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

if (
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This reads the nonce field for verification.
    ! isset($_POST['ppcart_fields_nonce']) ||
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This validates the nonce field.
    ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ppcart_fields_nonce'])), $this->plugin_name)
) {
    return $post_id;
}

$metas = $this->get_metabox_fields($object->post_type);

$stripe_objects = [];

foreach ($metas as $meta) {
    $new_value = '';

    $name = $meta[0];
    $field_type = $meta[1];

    if ('html' !== $field_type) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $posted_value is sanitized via $this->sanitizer() before persistence.
        $posted_value = isset($_POST[$name]) ? wp_unslash($_POST[$name]) : null;

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
