<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! is_array($fields)) {
    return;
}

$scripts = '';
$defaults['class_size']     = '';
$defaults['description']    = '';
$defaults['label']          = '';
$sections = $this->get_edit_field_sections($post_type);
$section_open = false;
$current_section_slug = '';

foreach ($fields as $atts) {
    if ($atts['type'] != 'repeater') {
        $atts = wp_parse_args($atts, $defaults);

        if ($atts['type'] == 'html') {
            echo wp_kses_post($atts['value']);
        } else {
            if (! empty($sections[$atts['id']])) {
                if ($section_open) {
                    $this->render_edit_section_after_fields($current_section_slug, $post_type);
                    echo '</div></section>';
                }

                $section = $sections[$atts['id']];
                echo '<section class="ppcart-edit-section ppcart-edit-section--' . esc_attr($section['slug']) . '">';
                echo '<header class="ppcart-edit-section__header"><h3>' . esc_html($section['title']) . '</h3></header>';
                echo '<div class="ppcart-edit-section__fields">';
                $section_open = true;
                $current_section_slug = $section['slug'];
                $this->render_edit_section_before_fields($current_section_slug, $post_type);
            }

            if (isset($this->meta[$atts['id']][0])) {
                if ($atts['type'] == 'checkbox') {
                    $atts['value'] = 1;
                } else {
                    $atts['value'] = $this->meta[$atts['id']][0];
                }
            }

            if ('_ppcart_status' === $atts['id'] && isset($atts['value'])) {
                $atts['value'] = PPCart_Status_Labels::edit_select_value($atts['value']);
            }

            apply_filters($this->plugin_name . '-field-' . $atts['id'], $atts);
            $atts['name'] = $atts['id'];
            $field_slug = sanitize_html_class(str_replace('_ppcart_', '', $atts['id']));
            $field_classes = array_filter(
                [
                    'ppcart-field',
                    'ppcart-row',
                    'ppcart-edit-field',
                    'ppcart-edit-field--' . $field_slug,
                    $atts['class_size'],
                ]
            );

            if ($atts['type'] != 'hidden') :
                ?><div id="rid<?php echo esc_attr($atts['id']); ?>" class="<?php echo esc_attr(implode(' ', $field_classes)); ?>"><?php
            endif;
            if (file_exists((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-' . $atts['type'] . '.php')) {
                include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-' . $atts['type'] . '.php');
            } else {
                include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-text.php');
            }
            if ($atts['type'] != 'hidden') :
                ?></div><?php
            endif;
        }
        // conditional logic
        if (!empty($atts['conditional_logic'])) :
            $scripts .= ppcart_admin_conditional_logic_js_order_metabox(
                $atts['conditional_logic'],
                'rid' . $atts['id']
            );
        endif;
    } else {
        $setatts = $atts;

        apply_filters($this->plugin_name . '-field-repeater-' . $setatts['id'], $setatts);

        $count      = 1;
        $repeater   = [];

        if (! empty($this->meta[$setatts['id']])) {
            $repeater = maybe_unserialize($this->meta[$setatts['id']][0]);
        }

        if (! empty($repeater)) {
            $count = count($repeater);
        }

        include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . '' . 'ppcart-admin-field-repeater.php');
    }
}
if ($section_open) {
    $this->render_edit_section_after_fields($current_section_slug, $post_type);
    echo '</div></section>';
}
if ($scripts != '') {
    wp_add_inline_script(
        'ppcart-repeater',
        sprintf(
            "jQuery('document').ready(function($) { %s });",
            $scripts
        )
    );
}
