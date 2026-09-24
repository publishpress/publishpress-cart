<?php

if (! defined('ABSPATH')) {
    exit;
}



$hide_values = function_exists('ppcart_custom_meta_values')
    ? ppcart_custom_meta_values(is_array($this->meta) ? $this->meta : [], 'hide_fields')
    : (isset($this->meta[ ppcart_meta_key('hide_fields') ]) ? $this->meta[ ppcart_meta_key('hide_fields') ] : null);
$hide_fields = (is_array($hide_values) && isset($hide_values[0])) ? maybe_unserialize($hide_values[0]) : [];
foreach ($fields as $atts) {
    $defaults['class_size']     = '';
    $defaults['description']    = '';
    $defaults['label']          = '';
    $defaults['id']             = '';

    $atts = wp_parse_args($atts, $defaults);

    if ($atts['type'] != 'repeater' && $atts['type'] != 'conditions') {
        if ($atts['type'] == 'html') {
            echo wp_kses_post($atts['value']);

            if (ppcart_is_meta_field_id($atts['id'], 'default_fields') || ppcart_is_meta_field_id($atts['id'], 'address_fields')) {
                $setatts = $atts;

                apply_filters($this->plugin_name . '-field-repeater-' . $setatts['id'], $setatts);

                $count      = 0;
                $repeater   = [];

                $default_values = ppcart_custom_meta_values(is_array($this->meta) ? $this->meta : [], 'default_fields');
                if (is_array($default_values) && isset($default_values[0])) {
                    $repeater = maybe_unserialize($default_values[0]);
                }

                include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-default-fields.php');
            }
            apply_filters('ppcart_defualt_fields_html', $atts, $this->meta, $this->plugin_name, plugin_dir_path(__FILE__));
        } else {
            if ($atts['type'] == 'checkbox') {
                $atts['value'] = isset($this->meta[$atts['id']][0]);
            } elseif (isset($this->meta[$atts['id']][0])) {
                $atts['value'] = $this->meta[$atts['id']][0];
            }

            apply_filters($this->plugin_name . '-field-' . $atts['id'], $atts);
            $atts['name'] = $atts['id'];

            ?><div id="rid<?php echo esc_attr($atts['id']); ?>" class="ppcart-field ppcart-row <?php echo esc_attr($atts['class_size']); ?>"><?php
            if (file_exists((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-' . $atts['type'] . '.php')) {
                include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-' . $atts['type'] . '.php');
            } else {
                include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-text.php');
            }
            ?></div><?php
        }
        // conditional logic
        if (!empty($atts['conditional_logic'])) {
            $this->scripts .= ppcart_admin_conditional_logic_js_combined(
                $atts['conditional_logic'],
                'rid' . $atts['id'],
                $atts['id']
            );
        }
    } else {
        $setatts = $atts;

        apply_filters($this->plugin_name . '-field-repeater-' . $setatts['id'], $setatts);

        $count      = 0;
        $repeater   = [];

        if (! empty($this->meta[$setatts['id']])) {
            $repeater = maybe_unserialize($this->meta[$setatts['id']][0]);
        }

        if (! empty($repeater)) {
            $count = count($repeater);
        }

        // conditional logic
        if (!empty($atts['conditional_logic'])) {
            $this->scripts .= ppcart_admin_conditional_logic_js_combined(
                $atts['conditional_logic'],
                'repeater' . $atts['id'],
                $atts['id']
            );
        }

        include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-repeater.php');
    }
}
