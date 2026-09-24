<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately in this condition.
if (!isset($_POST['nonce']) || !ppcart_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ppcart_ajax_nonce')) {
    wp_send_json_error(__('Invalid Request', 'publishpress-cart'), 401);
}
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

if (! current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to perform this action.', 'publishpress-cart'), 403);
}

$group_id = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
if ($group_id === '') {
    wp_send_json_error(__('Missing Fields', 'publishpress-cart'), 400);
}

$groups = get_option('ppcart_mailchimp_groups');

foreach ($groups as $k => $v) {
    if ($k == $group_id) {
        $opts = '<option> ' . esc_html__('Select', 'publishpress-cart') . ' </option>';
        foreach ($v as $tag_id => $label) {
            echo esc_html($tag_id . ' - ' . $label);
            $opts = '<option id="' . esc_attr($tag_id) . '">' . esc_html($label) . '</option>';
        }
    }
    echo wp_kses($opts, ['option' => ['value' => true, 'selected' => true, 'id' => true], 'optgroup' => ['label' => true]]);
    wp_die();
}
echo '<option>' . esc_html__('No groups found', 'publishpress-cart') . '</option>';
wp_die();
// phpcs:enable WordPress.Security.NonceVerification.Missing
