<?php

if (! defined('ABSPATH')) {
    exit;
}


$fields = $this->get_options_list();
foreach ($fields as $section => $sfields) {
    foreach ($sfields as $k => $v) {
        $settings_page = $this->plugin_name;
        // Allow individual fields to opt into a subsection (renders
        // under a different card on the same tab) without changing
        // their parent section key (preserving back-compat with
        // ppcart_option_list filter usage).
        $effective_section = ! empty($v['subsection']) ? $v['subsection'] : $section;
        $v['settings']['section'] = $effective_section;
        if (!empty($v['tab'])) {
            $settings_page = $this->plugin_name . '-' . $v['tab'];
        }
        add_settings_field(
            $k,
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook cannot gain a separator without breaking integrations.
            apply_filters('ppcartlabel-' . $k, $v['label']),
            [$this, 'field_' . $v['type']],
            $settings_page,
            $this->plugin_name . '-' . $effective_section,
            $v['settings']
        );
        $callback = $this->get_field_sanitizer($v);
        if (empty($v['settings']['skip_register'])) {
            register_setting(
                $this->plugin_name . '-settings',
                $v['settings']['id'],
                ['sanitize_callback' => $callback]
            );
        }
    }
}
