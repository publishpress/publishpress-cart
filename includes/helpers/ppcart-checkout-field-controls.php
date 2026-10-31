<?php

/**
 * Native checkout grid defaults and controls shared by rendering and typed integrations.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

/** @since 1.1.0 @param string $group Native group. @return array */
function ppcart_checkout_field_defaults($group)
{
    $default_fields = [];
    if ('default' === $group) {
        $group = 'default';
        $default_fields = [
        'first_name' => ['name' => 'first_name','label' => esc_html__('First Name', 'publishpress-cart'),'required' => true,'cols' => 6],
        'last_name' => ['name' => 'last_name','label' => esc_html__('Last Name', 'publishpress-cart'),'required' => true,'cols' => 6],
        'email' => ['name' => 'email','label' => esc_html__('Email', 'publishpress-cart'),'type' => 'email','required' => true,'cols' => 6],
        'phone' => ['name' => 'phone','label' => esc_html__('Phone Number', 'publishpress-cart'),'cols' => 6],
        'company' => ['name' => 'company','label' => esc_html__('Company Name', 'publishpress-cart'),'required' => false,'cols' => 6],
        ];
    } elseif ('address' === $group) {
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

    return $default_fields;
}

/** @since 1.1.0 @param array $field Native defaults. @param bool $hidden Hidden state. @return array */
function ppcart_checkout_field_controls(array $field, $hidden = false)
{
    return [
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
}
