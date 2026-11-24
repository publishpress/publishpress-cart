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
                'tooltip' => __('Shown on the product page, orders, emails and Stripe payments.', 'publishpress-cart'),
                'id'            => '_ppcart_product_name',
                'label'           => __('Public product name', 'publishpress-cart'),
                'placeholder' => __('Leave empty to use the main product title', 'publishpress-cart'),
                'type'            => 'text',
            ],
            [
                'class'           => 'widefat',
                'description' => __('Hide the name in the product page header.', 'publishpress-cart'),
                'id'            => '_ppcart_hide_title',
                'label'           => __('Hide page title', 'publishpress-cart'),
                'placeholder' => '',
                'type'            => 'checkbox',
                'value'           => '',
                'class_size'  => '',
            ],
            [
                'id' => '_ppcart_show_in_shop',
                'label' => __('Show in Shop Page', 'publishpress-cart'),
                'description' => __('List this product on the Shop page.', 'publishpress-cart'),
                'type' => 'checkbox',
            ],
            [
                'id' => '_ppcart_shop_general_submitted',
                'type' => 'html',
                'value' => '<input type="hidden" name="_ppcart_shop_general_submitted" value="1">',
            ],
            [
                'class'       => 'ppcart-color-field',
                'description' => '',
                'id'            => '_ppcart_header_color',
                'label'       => __('Header background color', 'publishpress-cart'),
                'placeholder' => '',
                'type'        => 'text',
                'value'       => '',
            ],
            [
                'class'       => 'widefat media-picker',
                'description' => '',
                'id'            => '_ppcart_header_image',
                'label'       => __('Header background image', 'publishpress-cart'),
                'label-remove'        => __('Remove image', 'publishpress-cart'),
                'label-upload'        => __('Set image', 'publishpress-cart'),
                'placeholder' => '',
                'type'        => 'file-upload',
                'field-type'      => 'url',
                'value'       => '',
            ],
        ];

        $this->general = apply_filters('ppcart_product_general_fields', $this->general, $post_id);
    }
}
