<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! empty($section['title'])) {
    // Use <h2> as a DIRECT child of the card so the legacy
    // email accordion JS (`.email_title_trigger.next('table')`)
    // continues to find the form-table as its next sibling.
    if ('integrations' !== $tab_slug && ! $is_headingless_card) {
        echo '<h2 class="ppcart-settings__card-title">' . esc_html($section['title']) . '</h2>';
    }
}

if (! empty($section['callback']) && is_callable($section['callback'])) {
    call_user_func($section['callback'], $section);
}

echo '<table class="form-table" role="presentation">';
if ('integrations' === $tab_slug) {
    foreach ($section_fields as $field_id => $field) {
        if (in_array((string) $field_id, $card_toggle_field_ids, true)) {
            $field_args = isset($field['args']) && is_array($field['args']) ? $field['args'] : [];
            $field_args['data'] = isset($field_args['data']) && is_array($field_args['data']) ? $field_args['data'] : [];
            $field_args['data']['pp-integration-toggle'] = $integration_key;
            $field['args'] = $field_args;
        }

        $render_setting_field_row($field);
    }
} else {
    do_settings_fields($page_slug, $section['id']);

    // Append locked Pro field previews to their target free card.
    if (function_exists('ppcart_pro_locked_settings_field_sections') && function_exists('ppcart_pro_locked_field_rows_html')) {
        $locked_field_sections = ppcart_pro_locked_settings_field_sections();
        if (isset($locked_field_sections[$tab_slug]) && $plugin_name . '-' . $locked_field_sections[$tab_slug] === $section['id']) {
            echo wp_kses(ppcart_pro_locked_field_rows_html($tab_slug), ppcart_admin_allowed_html());
        }
    }
}
echo '</table>';
