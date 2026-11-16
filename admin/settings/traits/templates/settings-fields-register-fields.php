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
        // Tie the row label (the Settings API field title) to the control so the
        // control gets an accessible name (WCAG 1.3.1, 4.1.2). Only for field
        // types whose partial renders one control with this DOM id.
        $label_for_types = [ 'text', 'email', 'password', 'color', 'textarea', 'upload', 'select', 'checkbox' ];
        if (
            empty($v['settings']['label_for'])
            && ! empty($v['settings']['id'])
            && in_array($v['type'] ?? '', $label_for_types, true)
        ) {
            if (! empty($v['settings']['html_id'])) {
                $v['settings']['label_for'] = $v['settings']['html_id'];
            } elseif ('checkbox' === $v['type'] && ! empty($v['settings']['rid'])) {
                $v['settings']['label_for'] = $v['settings']['rid'];
            } else {
                $v['settings']['label_for'] = $v['settings']['id'];
            }
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
