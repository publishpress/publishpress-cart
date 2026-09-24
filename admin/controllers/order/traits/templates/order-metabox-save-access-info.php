<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately below.
$nonce = isset($_POST['ppcart_related_product']) ? sanitize_text_field(wp_unslash($_POST['ppcart_related_product'])) : '';
if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_related_product')) {
    return;
}
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the metabox nonce check above.

if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
}

$submitted_post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';

if ('page' === $submitted_post_type) {
    if (! current_user_can('edit_page', $post_id)) {
        return;
    }
} else {
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }
}

if (array_key_exists('_ppcart_related_product', $_POST)) {
    ppcart_update_post_meta(
        $post_id,
        'related_product',
        isset($_POST['_ppcart_related_product']) ? absint(wp_unslash($_POST['_ppcart_related_product'])) : 0
    );
} else {
    ppcart_delete_post_meta($post_id, 'related_product');
}
// phpcs:enable WordPress.Security.NonceVerification.Missing
