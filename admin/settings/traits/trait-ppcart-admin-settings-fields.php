<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once __DIR__ . '/trait-ppcart-admin-settings-text-fields.php';

require_once __DIR__ . '/trait-ppcart-admin-settings-input-fields.php';

trait PPCart_Admin_Settings_Fields_Trait
{
    use PPCart_Admin_Settings_Text_Fields_Trait;
    use PPCart_Admin_Settings_Input_Fields_Trait;

    /**
     * Registers settings fields with WordPress
     */

    public function register_fields()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-fields-register-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
    /**
     * Return the native sanitizer for a settings field, including email and secret handling.
     *
     * @since 1.1.0
     * @param array $field Native field definition.
     * @return callable
     */
    public function get_field_sanitizer(array $field)
    {
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
        if ('select' === $field['type'] && false !== strpos($field['settings']['class'] ?? '', 'multiple')) {
            return static function ($value) {
                if (! is_array($value) || count(array_filter($value, 'is_array')) || count(array_filter($value, 'is_object'))) {
                    return [];
                }
                return array_values(array_map('sanitize_text_field', $value));
            };
        }
        $option_id = $field['settings']['id'];
        $callback = $sanitize_callbacks[$field['type']] ?? 'sanitize_text_field';
        if ('password' === $field['type'] || ! empty($field['settings']['secret'])) {
            $callback = static function ($val) use ($option_id) {
                return class_exists('PPCart_Secrets')
                    ? PPCart_Secrets::sanitize_secret_field($val, $option_id)
                    : sanitize_text_field((string) $val);
            };
        }
        if (! empty($field['settings']['email_template_field']) && 'body' === $field['settings']['email_template_field'] && function_exists('ppcart_kses_email_html')) {
            $callback = 'ppcart_kses_email_html';
        }
        return $callback;
    }

}
