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

if (ppcart_is_meta_field_id($setatts['id'], 'default_fields')) {
    $group = 'default';
    $default_fields = [
        'first_name' => ['name' => 'first_name','label' => esc_html__('First Name', 'publishpress-cart'),'required' => true,'cols' => 6],
        'last_name' => ['name' => 'last_name','label' => esc_html__('Last Name', 'publishpress-cart'),'required' => true,'cols' => 6],
        'email' => ['name' => 'email','label' => esc_html__('Email', 'publishpress-cart'),'type' => 'email','required' => true,'cols' => 6],
        'phone' => ['name' => 'phone','label' => esc_html__('Phone Number', 'publishpress-cart'),'cols' => 6],
        'company' => ['name' => 'company','label' => esc_html__('Company Name', 'publishpress-cart'),'required' => false,'cols' => 6],
    ];
} elseif (ppcart_is_meta_field_id($setatts['id'], 'address_fields')) {
    $group = 'address';
    $default_fields = [
        'country' => ['name' => 'country','label' => esc_html__('Country', 'publishpress-cart'),'required' => true,'cols' => 12],
        'address1' => ['name' => 'address1','label' => esc_html__('Address', 'publishpress-cart'),'required' => true,'cols' => 12],
        'address2' => ['name' => 'address2','label' => esc_html__('Address Line 2', 'publishpress-cart'),'cols' => 12],
        'city' => ['name' => 'city','label' => esc_html__('Town / City', 'publishpress-cart'),'required' => true,'cols' => 12],
        'state' => ['name' => 'state','label' => esc_html__('State / County', 'publishpress-cart'),'required' => true,'cols' => 6],
        'zip' => ['name' => 'zip','label' => esc_html__('Postcode / Zip', 'publishpress-cart'),'required' => true,'cols' => 6],
    ];
}

$repeater_id = "repeater" . $setatts['id'];
$repeater_testid = ppcart_testid('ppcart-admin-repeater-' . $setatts['id']);

$setatts['id'] = ppcart_meta_key('default_fields');
$setatts['label-edit'] = __('Edit Field', 'publishpress-cart');


?><ul id="<?php echo esc_attr($repeater_id); ?>" class="ppcart-repeaters" data-testid="<?php echo esc_attr($repeater_testid); ?>"><?php

    $i = 0;
$use_defaults = false;

$saved_data = $repeater ?? [];

if (!$repeater) {
    $use_defaults = true;
    $repeater = $default_fields;
} else {
    // add default field info if missing from saved data
    foreach ($default_fields as $key => $value) {
        if (!isset($repeater[$key])) {
            $repeater[$key] = $value;
        }
    }
}

foreach ($repeater as $key => $saved) {
    if (!isset($default_fields[$key])) {
        continue;
    }

    $field = $default_fields[$key];

    if (($use_defaults && !isset($field['required'])) || (!$use_defaults && !isset($saved['default_field_required']))) {
        $field['required'] = '';
    }

    $k = $field['name'];
    $key = str_replace('_', '', $key);

    $setatts['label-header'] = $field['label'];
    $setatts['class'] = 'ppcart-repeater';

    // check $saved_data to see if key exists. If not, it's a new default field and should be hidden by default
    $hidden = ($saved_data && !isset($saved_data[$k])) ? true : isset($hide_fields[$key]);

    if ($hidden) {
        $setatts['class'] .= ' disabled';
    }

    $setatts['fields'] = [
        [
        'checkbox' => [
            'class'     => 'default_field_disabled',
            'description'   => '',
            'id'            => 'default_field_disabled',
            'label'     => __('Disabled', 'publishpress-cart'),
            'placeholder'   => '',
            'type'      => 'checkbox',
            'value'     => $hidden,
            'class_size'    => 'one-half first',
        ]],
        [
        'checkbox' => [
            'class'     => '',
            'description'   => '',
            'id'            => 'default_field_required',
            'label'     => __('Required Field', 'publishpress-cart'),
            'placeholder'   => '',
            'type'      => 'checkbox',
            'value'     => $field['required'] ?? '',
            'class_size'    => 'one-half',
        ]],
        [
        'text' => [
            'class'         => 'widefat repeater-title required',
            'description'   => '',
            'id'            => 'default_field_label',
            'label'         => __('Label', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'text',
            'value'         => $field['label'],
            'class_size'    => 'one-half first',
        ]],
        [
        'select' => [
            'class'         => '',
            'description'   => '',
            'id'            => 'default_field_size',
            'label'         => __('Size', 'publishpress-cart'),
            'placeholder'   => '',
            'type'          => 'select',
            'value'         => $field['cols'],
            'selections'    =>  [
                '12'        => __('Large', 'publishpress-cart'),
                '6'         => __('Medium', 'publishpress-cart'),
            ],
            'class_size'    => 'one-half',
        ]],

    ];

    $setatts = apply_filters('ppcart_default_field_settings_attributes', $setatts);
    $setatts = apply_filters('ppcart_default_' . $field['name'] . '_field_settings_attributes', $setatts);

    ?><li class="<?php echo esc_attr($setatts['class']); ?>" data-testid="<?php echo esc_attr(ppcart_testid($repeater_testid . '-row-' . $k)); ?>">
            <div class="handle">
                <span class="title-repeater" data-label="<?php echo esc_attr($setatts['label-header']); ?>"><?php echo esc_html($setatts['label-header']); ?></span>
                <button aria-expanded="true" class="ppcart-btn-edit" type="button" data-testid="<?php echo esc_attr(ppcart_testid($repeater_testid . '-edit-' . $k)); ?>">
                    <span class="screen-reader-text"><?php echo esc_html($setatts['label-edit']); ?></span>
                    <span class="toggle-arrow"></span>
                </button>
            </div><!-- .handle -->
            <div class="ppcart-repeater-content">
                <div class="wrap-fields"> <?php

                $defaults['class_size']     = '';
    $defaults['description']    = '';
    $defaults['label']          = '';


    foreach ($setatts['fields'] as $fieldcount => $field) {
        foreach ($field as $field_type => $atts) {
            $atts = wp_parse_args($atts, $defaults);

            if (! empty($repeater) && isset($repeater[$k][$atts['id']])) {
                $atts['value'] = $repeater[$k][$atts['id']];
            }

            if (isset($setting_field) && $setting_field) {
                if (! empty($repeater) && ! empty($repeater[$k][$atts['key']])) {
                    $atts['value'] = $repeater[$k][$atts['key']];
                }
            }

            $atts['name'] = $setatts['id'] . '[' . $k . '][' . $atts['id'] . ']';

            ?><div class="rid<?php echo esc_attr($atts['id']); ?> wrap-field <?php echo esc_attr($atts['class_size']); ?>"><?php

            if ($field_type == 'checkbox') {
                $atts['rid'] = $atts['name'];
            }

            ppcart_admin_field_set_repeater_html_id($atts, $setatts['id'], (string) $k);

            include(plugin_dir_path(__FILE__) . 'ppcart-admin-field-' . $field_type . '.php');

            ?></div><?php
        }
    } // $fieldset foreach

    ?></div>
            </div>
        </li><!-- .repeater --><?php

        $i++;
} // foreach field type

?>
</ul><!-- repeater -->
