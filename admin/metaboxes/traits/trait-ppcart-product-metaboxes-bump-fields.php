<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Product metabox field definitions for order bumps.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Product_Metaboxes_Bump_Fields_Trait
{
    /**
     * Order-bump repeater field schema for the product metabox.
     *
     * @param bool $save Unused; kept for the metabox save/render call signature.
     * @return array
     */
    public static function multi_order_bump_fields($save = false)
    {

        $fields = [
            // A switch in the row header. Saved rows without the key stay off; new rows start on.
            ['checkbox' => [
                'class'     => 'ppcart-repeater-enable-input',
                'description'   => '',
                'id'            => 'order_bump',
                'label'     => __('Enable', 'publishpress-cart'),
                'placeholder'   => '',
                'type'      => 'checkbox',
                'value'     => '',
                'new_row_value' => 1,
                'class_size'        => 'ppcart-repeater-enable',
                'header_toggle' => true,
            ]],
            ['text' => [
                'class'     => 'ppcart-color-field',
                'description'   => '',
                'id'            => 'bump_bg_color',
                'label'     => __('Background color', 'publishpress-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
                'class_size'    => '',
            ]],
            ['select' => [
                'class'         => 'update-plan-product required repeater-title',
                'description'   => '',
                'id'            => 'ob_product',
                'label'         => __('Product', 'publishpress-cart'),
                'placeholder'   => '',
                'type'          => 'select',
                'value'         => '',
                'selections'    => PPCart_Product_Metabox_Option_Sources::product_options(),
                'class_size' => '',
            ]],
            ['select' => [
                'class'         => '',
                'description'   => '',
                'id'            => 'ob_type',
                'label'         => __('Price type', 'publishpress-cart'),
                'placeholder'   => '',
                'type'          => 'select',
                'value'         => '',
                'selections'    => ['' => __('Enter price', 'publishpress-cart'), 'plan' => __('Existing payment plan', 'publishpress-cart')],
                'class_size' => '',
            ]],
            ['select' => [
                'class'         => 'widefat update-plan ob-{val}',
                'description'   => __('One-time plans only.', 'publishpress-cart'),
                'id'            => 'ob_plan',
                'label'         => __('Payment plan', 'publishpress-cart'),
                'placeholder'   => '',
                'value'         => '',
                'selections'    => PPCart_Product_Metabox_Option_Sources::get_plans('order_bump_options'),
                'class_size'    => '',
                'step'          => 'any',
                'type'          => 'select',
                'conditional_logic' => [
                    [
                        'field' => 'ob_type',
                        'value' => 'plan',
                    ],
                ],
            ]],
            ['text' => [
                'class'         => 'widefat',
                'description'   => '',
                'id'            => 'ob_price',
                'label'         => __('Price', 'publishpress-cart'),
                'placeholder'   => '',
                'type'          => 'price',
                'value'         => '',
                'class_size'    => '',
                'conditional_logic' => [
                    [
                        'field' => 'ob_type',
                        'value' => 'plan',
                        'compare' => '!=',
                    ],
                ],
            ]],
            ['file-upload' => [
                'class'     => 'widefat',
                'description'   => '',
                'id'            => 'ob_image',
                'label'     => __('Product image', 'publishpress-cart'),
                'label-remove'      => __('Remove image', 'publishpress-cart'),
                'label-upload'      => __('Set image', 'publishpress-cart'),
                'placeholder'   => '',
                'type'      => 'file-upload',
                'field-type'        => 'url',
                'value'     => '',
                'class_size' => '',
            ]],
            ['select' => [
                'class'         => '',
                'description'   => '',
                'id'            => 'ob_image_pos',
                'label'         => __('Image position', 'publishpress-cart'),
                'placeholder'   => '',
                'type'          => 'select',
                'value'         => '',
                'selections'    => ['' => __('Left', 'publishpress-cart'), 'top' => __('Top', 'publishpress-cart')],
                'class_size' => '',
            ]],
            ['text' => [
                'class'     => 'widefat',
                'description'   => '',
                'id'            => 'ob_cb_label',
                'label'     => __('Checkbox label', 'publishpress-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
                'class_size'    => '',
            ]],
            ['text' => [
                'class'     => 'widefat',
                'description'   => '',
                'id'            => 'ob_headline',
                'label'     => __('Headline', 'publishpress-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
                'class_size'    => '',
            ]],
            ['textarea' => [
                'class'     => '',
                'description'   => '',
                'id'            => 'ob_description',
                'label'     => __('Product description', 'publishpress-cart'),
                'placeholder'   => '',
                'type'      => 'textarea',
                'value'     => '',
                'class_size'    => '',
            ]],
        ];

        return $fields;
    }

    public function add_consent_field($fields, $save)
    {
        $fields[1]['fields'][] = [
            'checkbox' => [
                'class'     => '',
                'description'   => __('Run only for customers who opt in.', 'publishpress-cart'),
                'tooltip'       => __('Turn on Show opt-in checkbox first.', 'publishpress-cart'),
                'id'            => 'require_optin',
                'label'     => __('Require consent', 'publishpress-cart'),
                'placeholder'   => '',
                'type'      => 'checkbox',
                'value'     => '',
                'conditional_logic' =>  [
                    [
                        'field' => 'services',
                        'value' => ppcart_consent_services(), // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                        'compare' => 'IN', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                    ],
                ],
            ],
        ];
        return $fields;
    }
}
