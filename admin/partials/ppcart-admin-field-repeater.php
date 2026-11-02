<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for a repeater field
 *
 * Must include an multi-dimensional array with each field in it. The
 * field type should be the key for the field's attribute array.
 *
 * $fields['file-type']['all-the-field-attributes'] = 'Data for the attribute';
 *
 * @link https://publishpress.com/cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

$repeater_id = "repeater" . $setatts['id'];
$repeater_testid = ppcart_testid('ppcart-admin-repeater-' . $setatts['id']);
$didscripts = false;

if (isset($repeater) && is_array($repeater) && isset($setatts['id'])) {
    $repeater = apply_filters('ppcart_repeater_saved_rows', $repeater, $setatts['id']);
}

// Render a visible starter row when no repeater items exist yet, unless the
// repeater shows an empty state instead.
$empty_state  = (! empty($setatts['empty_state']) && is_array($setatts['empty_state'])) ? $setatts['empty_state'] : [];
$render_count = (int) $count;
$is_starter   = false;
if (0 === $render_count && [] === $empty_state) {
    $render_count = 1;
    $is_starter   = true;
}

// Optional render-time sections: field id => section, so the field arrays keep their order.
$repeater_sections = (! empty($setatts['sections']) && is_array($setatts['sections'])) ? $setatts['sections'] : [];

?><ul id="<?php echo esc_attr($repeater_id); ?>" class="ppcart-repeaters<?php echo [] !== $repeater_sections ? ' ppcart-repeaters--sections' : ''; ?>" data-testid="<?php echo esc_attr($repeater_testid); ?>"><?php

if ([] !== $empty_state) {
    ?><li class="ppcart-repeater-empty"<?php echo 0 === (int) $count ? '' : ' hidden'; ?>>
        <span class="ppcart-repeater-empty__icon dashicons <?php echo esc_attr($empty_state['icon'] ?? 'dashicons-plus-alt2'); ?>" aria-hidden="true"></span>
        <p class="ppcart-repeater-empty__title"><?php echo esc_html($empty_state['title'] ?? ''); ?></p>
        <?php if (! empty($empty_state['text'])) : ?>
            <p class="ppcart-repeater-empty__text"><?php echo esc_html($empty_state['text']); ?></p>
        <?php endif; ?>
    </li><?php
}

    // Each row starts from the default title, so an untitled row (or the template) never shows the title of the row before it.
    $default_title = (string) ($setatts['label-header'] ?? '');
    $title_field   = (string) ($setatts['title-field'] ?? '');

    for ($i = 0; $i <= $render_count; $i++) {
        $k = $i;
        if ($i === $render_count) {
            $setatts['class'] .= ' hidden';
            $k = null;
        }

        $row_title = ('' !== $title_field && ! empty($repeater[$i][$title_field])) ? (string) $repeater[$i][$title_field] : $default_title;

        $row_testid_suffix = null === $k ? 'template' : (string) $k;
        $row_class = $setatts['class'];
        if ($is_starter && null !== $k) {
            $row_class .= ' is-starter';
        }
        $row_entry = (isset($repeater[$i]) && is_array($repeater[$i])) ? $repeater[$i] : [];
        if (function_exists('ppcart_repeater_entry_is_enabled') && ! ppcart_repeater_entry_is_enabled($row_entry)) {
            $row_class .= ' is-disabled';
        }

        $defaults['class_size']     = '';
        $defaults['description']    = '';
        $defaults['label']          = '';

        $header_toggle_fields = [];
        $body_fields = [];

        foreach ($setatts['fields'] as $field) {
            foreach ($field as $field_type => $atts) {
                if (($atts['id'] == 'ob_plan' || $atts['id'] == 'prod_plan') && !empty($prod_id)) {
                    $atts['selections'] = $atts['selections'][$prod_id];
                }

                $atts = wp_parse_args($atts, $defaults);

                if (! empty($repeater) && isset($repeater[$i][$atts['id']])) {
                    $atts['value'] = $repeater[$i][$atts['id']];
                    if ($atts['id'] ==  'ob_product' || $atts['id'] ==  'prod_product') {
                        $prod_id = $atts['value'];
                    }
                }

                // Rows the user adds can start from another value than saved rows that lack the key.
                if (null === $k && array_key_exists('new_row_value', $atts)) {
                    $atts['value'] = $atts['new_row_value'];
                }

                if (isset($setting_field) && $setting_field) {
                    if (! empty($repeater) && ! empty($repeater[$i][$atts['key']])) {
                        $atts['value'] = $repeater[$i][$atts['key']];
                    }
                }

                // Ensure default visible repeater rows have a required unique id.
                if (null !== $k && isset($atts['class']) && false !== strpos((string) $atts['class'], 'ppcart-unique')) {
                    if (! isset($atts['value']) || '' === (string) $atts['value']) {
                        $atts['value'] = uniqid();
                    }
                }

                $atts['name'] = sprintf('%s[%s][%s]', $setatts['id'], $atts['id'], $k);

                if ('editor' !== $field_type) {
                    $repeater_row_key = (null === $k) ? 'hidden' : (string) $k;
                    ppcart_admin_field_set_repeater_html_id($atts, $setatts['id'], $repeater_row_key);
                }

                if ('editor' === $field_type) {
                    $editor_suffix = (null === $k) ? 'template' : (string) $k;
                    $atts['editor_id'] = sanitize_key($setatts['id'] . '_' . $atts['id'] . '_' . $editor_suffix);
                    $atts['defer_editor'] = true;
                }

                if ('checkbox' === $field_type) {
                    $atts['rid'] = $atts['name'];
                }

                $prepared = [
                    'field_type' => $field_type,
                    'atts'       => $atts,
                ];

                if (! empty($atts['header_toggle'])) {
                    $header_toggle_fields[] = $prepared;
                } else {
                    $body_fields[] = $prepared;
                }
            }
        }

        ?><li class="<?php echo esc_attr($row_class); ?>" data-testid="<?php echo esc_attr(ppcart_testid($repeater_testid . '-row-' . $row_testid_suffix)); ?>">
            <div class="handle">
                <span class="title-repeater" data-label="<?php echo esc_attr($default_title); ?>"><?php echo esc_html($row_title); ?></span>
                <?php if (! empty($setatts['summary'])) : ?>
                    <span class="ppcart-repeater-summary"></span>
                <?php endif; ?>
                <?php if (! empty($header_toggle_fields)) : ?>
                    <span class="ppcart-repeater-disabled-badge"><?php esc_html_e('Disabled', 'publishpress-cart'); ?></span>
                    <?php foreach ($header_toggle_fields as $prepared) :
                        $field_type = $prepared['field_type'];
                        $atts = $prepared['atts'];
                        ?>
                    <div class="rid<?php echo esc_attr($atts['id']); ?> wrap-field ppcart-repeater-enable ppcart-field--<?php echo esc_attr(sanitize_html_class((string) ($atts['type'] ?? $field_type))); ?> <?php echo esc_attr($atts['class_size']); ?>">
                        <?php
                        if (file_exists(plugin_dir_path(__FILE__) . 'ppcart-admin-field-' . $field_type . '.php')) {
                            include(plugin_dir_path(__FILE__) . 'ppcart-admin-field-' . $field_type . '.php');
                        } else {
                            include(plugin_dir_path(__FILE__) . 'ppcart-admin-field-text.php');
                        }
                        ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <button aria-expanded="true" class="ppcart-btn-edit" type="button" data-testid="<?php echo esc_attr(ppcart_testid($repeater_testid . '-edit-' . $row_testid_suffix)); ?>">
                    <span class="screen-reader-text"><?php echo esc_html($setatts['label-edit']); ?></span>
                    <span class="toggle-arrow"></span>
                </button>
            </div><!-- .handle -->
            <div class="ppcart-repeater-content">
                <div class="wrap-fields"> <?php

        $body_groups = [ '' => $body_fields ];
        if ([] !== $repeater_sections) {
            $body_groups = ppcart_admin_repeater_group_fields($body_fields, $repeater_sections);
        }

        foreach ($body_groups as $section_id => $group_fields) {
            $section = ('' !== $section_id) ? $repeater_sections[ $section_id ] : [];
            $section_type = ('details' === ($section['type'] ?? '')) ? 'details' : 'div';
            $section_class = 'ppcart-repeater-section ppcart-repeater-section--' . sanitize_html_class((string) $section_id);

            if ('' !== $section_id && 'details' === $section_type) {
                ?><details class="<?php echo esc_attr($section_class); ?>"><summary class="ppcart-repeater-section__title"><?php echo esc_html($section['title'] ?? ''); ?></summary><div class="ppcart-repeater-section__fields"><?php
            } elseif ('' !== $section_id) {
                ?><div class="<?php echo esc_attr($section_class); ?>"><?php
                if (! empty($section['title'])) {
                    ?><p class="ppcart-repeater-section__title"><?php echo esc_html($section['title']); ?></p><?php
                }
                ?><div class="ppcart-repeater-section__fields"><?php
            }

        foreach ($group_fields as $prepared) {
            $field_type = $prepared['field_type'];
            $atts = $prepared['atts'];

            ?><div class="rid<?php echo esc_attr($atts['id']); ?> wrap-field ppcart-field--<?php echo esc_attr(sanitize_html_class((string) ($atts['type'] ?? $field_type))); ?> <?php echo esc_attr($atts['class_size']); ?>"><?php

            if ('html' === $field_type) {
                $allowed_html = wp_kses_allowed_html('post');

                $allowed_html['button'] = [
                    'aria-expanded'         => true,
                    'class'                 => true,
                    'type'                  => true,
                    'data-ppcart-notif-preview' => true,
                    'data-testid'           => true,
                ];

                echo wp_kses($atts['value'], $allowed_html);
            } elseif (file_exists(plugin_dir_path(__FILE__) .  'ppcart-admin-field-' . $field_type . '.php')) {
                include(plugin_dir_path(__FILE__) . 'ppcart-admin-field-' . $field_type . '.php');
            } else {
                include(plugin_dir_path(__FILE__) . 'ppcart-admin-field-text.php');
            }

            ?></div><?php

            // conditional logic
            if (!empty($atts['conditional_logic']) && !$didscripts) {
                $this->scripts .= ppcart_admin_conditional_logic_js_repeater_rows(
                    $atts['conditional_logic'],
                    $setatts['id'],
                    $atts['id'],
                    $repeater_id
                );
            }
        } // $fieldset foreach

            if ('' !== $section_id) {
                echo 'details' === $section_type ? '</div></details>' : '</div></div>';
            }
        } // $body_groups foreach

        ?></div>
                <div>
                    <a class="link-remove" href="#" data-testid="<?php echo esc_attr(ppcart_testid($repeater_testid . '-remove-' . $row_testid_suffix)); ?>">
                        <span><?php

                    echo esc_html(apply_filters('ppcart-repeater-remove-link-label', $setatts['label-remove']));

        ?></span>
                    </a>
                </div>
            </div>
        </li><!-- .repeater --><?php

        $didscripts = true;
    } // for

?><div class="repeater-more">
        <span class="status"></span>
        <a class="button add-repeater" href="#" data-testid="<?php echo esc_attr(ppcart_testid($repeater_testid . '-add')); ?>"><?php

        echo esc_html(apply_filters('ppcart-repeater-more-link-label', $setatts['label-add']));

?></a>
    </div><!-- .repeater-more -->
</ul><!-- repeater -->
