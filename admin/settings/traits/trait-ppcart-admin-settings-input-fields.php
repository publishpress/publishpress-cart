<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Settings_Input_Fields_Trait
{
    public function sanitize_checkbox($val)
    {
        $val = ($val) ? 1 : 0 ;
        return $val;
    }

    /**
         * Masks stored secret values in admin field attributes.
         *
         * @param array $atts Field attributes.
         * @return void
         */
    private function apply_secret_field_mask(array &$atts)
    {
        $option_id = isset($atts['id']) ? (string) $atts['id'] : '';
        $stored    = '' !== $option_id ? ppcart_get_sensitive_option($option_id, '') : '';

        if ('' === $stored || ! is_string($stored)) {
            return;
        }

        $atts['value']                  = '';
        $atts['data-pp-secret-stored']  = '1';
        $atts['placeholder']            = __('Leave blank to keep current value', 'publishpress-cart');

        if (! empty($atts['readonly']) || ! empty($atts['secret'])) {
            $atts['placeholder'] = __('Configured (value hidden)', 'publishpress-cart');
        }
    }

    /**
         * Renders the Maintenance security panel.
         *
         * @param array $args Field arguments.
         * @return void
         */
    public function field_maintenance_secrets($args)
    {
        if (class_exists('PPCart_Secrets')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in render_maintenance_secret_storage_html().
            echo PPCart_Secrets::render_maintenance_secret_storage_html();
        }
    }

    private static function get_pages()
    {
        $pages = get_pages();
        $options = ['' => __('Select Page', 'publishpress-cart')];
        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title . ' (ID: ' . $page->ID . ')';
        }
        return $options;
    }

    /**
         * Creates a settings section
         *
         * @since 1.0.0
         * @param array         $params         Array of parameters for the section
         * @return      mixed                       The settings section
         */
    public function section_settings($params)
    {
    }

    /**
         * Creates a checkbox field
         *
         * @param array         $args           The arguments for the field
         * @return  string                      The HTML field
         */
    public function field_checkbox($args)
    {
        include __DIR__ . '/templates/settings-input-fields-field-checkbox.php';
    }

    /**
         * Creates an editor field
         *
         * NOTE: ID must only be lowercase letter, no spaces, dashes, or underscores.
         *
         * @param array         $args           The arguments for the field
         * @return  string                      The HTML field
         */
    public function field_editor($args)
    {
        include __DIR__ . '/templates/settings-input-fields-field-editor.php';
    }

    /**
         * Creates a set of radios field
         *
         * @param array         $args           The arguments for the field
         * @return  string                      The HTML field
         */
    public function field_radios($args)
    {
        include __DIR__ . '/templates/settings-input-fields-field-radios.php';
    }

    public function field_repeater($args)
    {
        include __DIR__ . '/templates/settings-input-fields-field-repeater.php';
    }

    /**
         * Fillter array remove empty value
         *
         *
         * @param array         $value          The arguments for the field
         * @return  array                       return filtered array
         *
         *
         */
    public function remove_repeater_blank($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) :
                if (empty($val)) {
                    unset($value[$key]);
                }
            endforeach;
        }
        return $value;
    }

    /**
         * Creates a select field
         *
         * Note: label is blank since its created in the Settings API
         *
         * @param array         $args           The arguments for the field
         * @return  string                      The HTML field
         */
    public function field_select($args)
    {
        include __DIR__ . '/templates/settings-input-fields-field-select.php';
    }
}
