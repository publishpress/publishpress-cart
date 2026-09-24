<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for a select field
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

$default_atts = [  'value' => '',
                        'placeholder' => '',
                        'class' => '',
                        'id' => '',
                     'note' =>  '',
                     'lock_icon' => false,
                     'disabled' => false];

$atts = wp_parse_args($atts, $default_atts);
$field_dom_id = ppcart_admin_field_dom_id($atts);
$testid_source = ! empty($atts['id']) ? $atts['id'] : ($atts['name'] ?? '');
$testid = ppcart_testid('ppcart-admin-field-' . $testid_source);

$atts['value'] = maybe_unserialize($atts['value']);

$replace = (isset($atts['value']) && $atts['value'] != '...') ? $atts['value'] : '';
if ($replace && strpos($atts['class'], '{val}') !== false) {
    $atts['class'] = str_replace('{val}', $replace, $atts['class']);
}

if (! empty($atts['label'])) {
    ?><label for="<?php echo esc_attr($field_dom_id); ?>"><?php echo esc_html($atts['label']); ?>

    <?php if (! empty($atts['description'])) :?>
        <i class="ppcart-tooltip" data-balloon-length="medium" aria-label="<?php echo esc_attr($atts['description']); ?>" data-balloon-pos="up" tabindex="0">?</i>
    <?php endif; ?>

    </label><?php
}
echo '<div class="input-group field-select" style="flex-grow: 1;">';
if ($atts['id'] == 'converkit_tags' || $atts['id'] == 'mail_groups' || $atts['id'] == 'mail_tags') {
    ?>
        <select
            aria-label="<?php echo esc_attr($atts['label']); ?>"
            class="<?php echo esc_attr($atts['class']); ?>"
            id="<?php echo esc_attr($field_dom_id); ?>"
            data-testid="<?php echo esc_attr($testid); ?>"
            name="<?php echo esc_attr($atts['name']); ?>[]">
<?php
} else {
    ?>
        <select
        <?php if (strpos($atts['class'], 'multiple') !== false) {
            $atts['name'] .= '[]';
            echo 'multiple="multiple"';
        } ?>
        aria-label="<?php echo esc_attr($atts['label']); ?>"
        class="<?php echo esc_attr($atts['class']); ?>"
        id="<?php echo esc_attr($field_dom_id); ?>"
        data-testid="<?php echo esc_attr($testid); ?>"
        <?php if ($atts['placeholder']) {
            echo 'data-placeholder="' . esc_attr($atts['placeholder']) . '"';
        } ?>
        <?php disabled(! empty($atts['disabled'])); ?>
        name="<?php echo esc_attr($atts['name']); ?>">
    <?php
}

if (! empty($atts['blank'])) {
    ?><option value><?php echo esc_html($atts['blank']); ?></option><?php
}
if (is_array($atts['selections'])) {
    foreach ($atts['selections'] as $value => $label) {
        if (is_array($label)) {
            foreach ($label as $v => $l) {
                ?>
                <optgroup label="<?php echo esc_attr($l['type'])?>">>
                <option
            value="<?php echo esc_attr($l['value']); ?>" <?php
                if (!is_countable($atts['value'])) {
                    selected($atts['value'], $l['value']);
                } elseif (in_array($l['value'], $atts['value'])) {
                    echo 'selected="selected"';
                } else {
                    ;
                } ?>><?php

                echo esc_html($l['name']);

                ?></option>
                </optgroup>
                <?php
            }
        } else {
            ?><option
            value="<?php echo esc_attr($value); ?>" <?php
                if (!is_countable($atts['value'])) {
                    selected($atts['value'], $value);
                } elseif (in_array($value, $atts['value'])) {
                    echo 'selected="selected"';
                } else {
                    ;
                } ?>><?php

                if (is_object($label)) {
                    echo esc_html($label->name);
                } else {
                    echo esc_html($label);
                }

            ?></option><?php
        }
    } // foreach
}
?></select>
<?php if (! empty($atts['lock_icon'])) : ?>
    <span class="dashicons dashicons-lock ppcart-field-lock" aria-hidden="true"></span>
<?php endif; ?>
<?php if (empty($atts['label']) && ! empty($atts['description'])) :?>
    <p class="description"><?php echo wp_kses_post(wp_specialchars_decode($atts['description'], ENT_QUOTES)); ?></p>
<?php endif; ?>
<?php if (! empty($atts['note'])) :?>
    <p class="description"><?php echo wp_kses_post(wp_specialchars_decode($atts['note'], ENT_QUOTES)); ?></p>
<?php endif; ?></div>
