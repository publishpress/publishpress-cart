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
        // _ppcart_option_list filter usage).
        $effective_section = ! empty($v['subsection']) ? $v['subsection'] : $section;
        $v['settings']['section'] = $effective_section;
        if (!empty($v['tab'])) {
            $settings_page = $this->plugin_name . '-' . $v['tab'];
        }
        add_settings_field(
            $k,
            apply_filters($this->plugin_name . 'label-' . $k, $v['label']),
            [$this, 'field_' . $v['type']],
            $settings_page,
            $this->plugin_name . '-' . $effective_section,
            $v['settings']
        );
        $sanitize_callbacks = [
            'checkbox' => [$this, 'sanitize_checkbox'],
            'editor'   => 'wp_kses_post',
            'textarea' => 'wp_kses_post',
            'url'      => 'esc_url_raw',
            'email'    => 'sanitize_email',
            'number'   => 'absint',
            'password' => 'sanitize_text_field',
            'text'     => 'sanitize_text_field',
            'select'   => 'sanitize_text_field',
        ];
        $option_id = $v['settings']['id'];
        $callback = $sanitize_callbacks[$v['type']] ?? 'sanitize_text_field';
        if ('password' === $v['type'] || ! empty($v['settings']['secret'])) {
            $callback = static function ($val) use ($option_id) {
                return class_exists('PPCart_Secrets')
                    ? PPCart_Secrets::sanitize_secret_field($val, $option_id)
                    : sanitize_text_field((string) $val);
            };
        }
        if (! empty($v['settings']['email_template_field']) && 'body' === $v['settings']['email_template_field'] && function_exists('ppcart_kses_email_html')) {
            $callback = 'ppcart_kses_email_html';
        }
        if (empty($v['settings']['skip_register'])) {
            register_setting(
                $this->plugin_name . '-settings',
                $v['settings']['id'],
                ['sanitize_callback' => $callback]
            );
        }
    }
}
