<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Product metabox general field group (name, description, media).
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Product_Metaboxes_General_Fields_Trait
{
    /**
     * Populate `$this->general` with the product metabox field definitions.
     *
     * @param int $post_id Product post ID.
     * @return void
     */
    private function set_general_field_group($post_id)
    {
        $this->general = [
            [
                'class'       => 'widefat',
                'description' => '',
                'id'            => '_ppcart_product_name',
                'label'           => __('Public Product Name', 'publishpress-cart'),
                'placeholder' => __('Leave empty to use the main product title', 'publishpress-cart'),
                'type'            => 'text',
            ],
            [
                'class'           => 'widefat',
                'description' => '',
                'id'            => '_ppcart_hide_title',
                'label'           => __('Hide Page Title', 'publishpress-cart'),
                'placeholder' => '',
                'type'            => 'checkbox',
                'value'           => '',
                'class_size'  => '',
            ],
            [
                'class'       => 'ppcart-color-field',
                'description' => '',
                'id'            => '_ppcart_header_color',
                'label'       => __('Header Background Color', 'publishpress-cart'),
                'placeholder' => '',
                'type'        => 'text',
                'value'       => '',
            ],
            [
                'class'       => 'widefat media-picker',
                'description' => '',
                'id'            => '_ppcart_header_image',
                'label'       => __('Header Background Image', 'publishpress-cart'),
                'label-remove'        => __('Remove Image', 'publishpress-cart'),
                'label-upload'        => __('Set Image', 'publishpress-cart'),
                'placeholder' => '',
                'type'        => 'file-upload',
                'field-type'      => 'url',
                'value'       => '',
            ],
        ];

        $this->general = apply_filters('ppcart_product_general_fields', $this->general, $post_id);
    }
}
