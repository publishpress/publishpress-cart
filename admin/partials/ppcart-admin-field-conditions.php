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
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPressCart
 * @subpackage PublishPressCart/admin/partials
 */

$oldatts = $setatts;
$oldrepeater = $repeater;
$oldrepeater_id = $repeater_id;

$attsname = $atts['name'];

$setatts = $atts;
$count2         = 0;
$repeater   = $atts['value'] ?? '';

if (! empty($repeater)) {
    $count2 = count($repeater);
} else {
    $count2 = 1;
}

$repeater_id = "repeater" . $setatts['id'];
$condition_testid = ppcart_testid('ppcart-admin-conditions-' . $setatts['id']);

$setatts['label-add'] = 'Add';
$setatts['label-remove'] = 'Remove';
$setatts['label-header'] = '';
$setatts['label-field'] = '';
$setatts['class'] = 'condition';

?><ul id="<?php echo esc_attr($repeater_id); ?>" class="conditions" data-testid="<?php echo esc_attr($condition_testid); ?>"><?php

    for ($i2 = 0; $i2 <= $count2; $i2++) {
        $k2 = $i2;
        if ($i2 === $count2) {
            $setatts['class'] .= ' hidden';
            $k2 = 'hidden';
        }

        ?><li class="<?php echo esc_attr($setatts['class']); ?>" data-testid="<?php echo esc_attr(ppcart_testid($condition_testid . '-row-' . $k2)); ?>">
            <div class="condition-content">
                <div class="wrap-fields condition-type-and">
                    <div class="ppcart-condition-type-and condition-type"><?php esc_html_e('And', 'publishpress-cart') ; ?></div>
                    <div class="ppcart-condition-type-or condition-type"><?php esc_html_e('Or', 'publishpress-cart') ; ?></div>
                    <?php

                    $defaults['class_size']     = '';
        $defaults['description']    = '';
        $defaults['label']          = '';


        foreach ($setatts['fields'] as $fieldcount => $field) {
            foreach ($field as $field_type => $atts) {
                if ($atts['id'] ==  'ob_plan' && !empty($prod_id)) {
                    $atts['selections'] = $atts['selections'][$prod_id];
                }

                $atts = wp_parse_args($atts, $defaults);

                if (! empty($repeater) && isset($repeater[$i2][$atts['id']])) {
                    $atts['value'] = $repeater[$i2][$atts['id']];
                }

                $atts['name']  = $attsname . '[' . $atts['id'] . ']' . '[' . $k2 . ']';
                $atts['class'] .= 'cinput-' . $atts['id'];

                ?><div class="rid<?php echo esc_attr($atts['id']); ?> wrap-field <?php echo esc_attr($atts['class_size']); ?>"><?php

                if ($field_type == 'checkbox') {
                    $atts['rid'] = $atts['name'];
                }

                ppcart_admin_field_set_repeater_html_id($atts, $setatts['id'], (string) $k2);

                include(plugin_dir_path(__FILE__) . 'ppcart-admin-field-' . $field_type . '.php');

                ?></div><?php
            }
        } // $fieldset foreach

        ?><a class="remove-condition" href="#" data-testid="<?php echo esc_attr(ppcart_testid($condition_testid . '-remove-' . $k2)); ?>">&times;</a>
                </div>
            </div>
        </li><!-- .condition --><?php
    } // for

?><div class="condition-more">
        <span class="status"></span>
        <a class="button add-condition" href="#" data-testid="<?php echo esc_attr(ppcart_testid($condition_testid . '-add')); ?>"><?php esc_html_e('+ Add another condition', 'publishpress-cart') ; ?></a>
    </div><!-- .repeater-more -->
</ul><!-- repeater -->

<?php
$setatts = $oldatts;
$repeater = $oldrepeater;
$repeater_id = $oldrepeater_id;
