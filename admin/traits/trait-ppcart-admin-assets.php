<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Admin stylesheet and script enqueue helpers for PublishPress Cart screens.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Assets_Trait
{
    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_styles($hook_suffix = '')
    {
        include __DIR__ . '/templates/admin-assets-enqueue-styles.php';
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts($hook_suffix)
    {
        include __DIR__ . '/templates/admin-assets-enqueue-scripts.php';
    }

    /**
     * Adds tax rate Underscore templates from the settings script handle.
     *
     * @return void
     */
    private function enqueue_tax_rate_templates()
    {
        $templates = include __DIR__ . '/templates/admin-assets-tax-rate-templates.php';

        if (! is_array($templates) || [] === $templates) {
            return;
        }

        wp_add_inline_script(
            'ppcart-settings',
            'window.ppcartTaxRateTemplates = ' . wp_json_encode($templates) . ';
            document.addEventListener("DOMContentLoaded", function () {
                Object.keys(window.ppcartTaxRateTemplates || {}).forEach(function (templateId) {
                    if (document.getElementById(templateId)) {
                        return;
                    }

                    var template = document.createElement("script");
                    template.type = "text/html";
                    template.id = templateId;
                    template.text = window.ppcartTaxRateTemplates[templateId];
                    document.body.appendChild(template);
                });
            });',
            'before'
        );
    }

    /**
     * Keep the main Gutenberg metabox panel visible on the product editor.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return void
     */
    private function enqueue_product_editor_metabox_panel_script($hook_suffix)
    {
        if (! $this->is_product_edit_screen($hook_suffix)) {
            return;
        }

        $live_product = function_exists('ppcart_live_post_type') ? ppcart_live_post_type('product') : PPCart_Admin_Screens::POST_TYPE_PRODUCT;
        if (function_exists('use_block_editor_for_post_type') && ! use_block_editor_for_post_type($live_product)) {
            return;
        }

        wp_enqueue_script(
            'ppcart-product-editor-metabox-panel',
            PPCART_BASE_URL . 'admin/js/ppcart-product-editor-metabox-panel.js',
            [ 'wp-data', 'wp-dom-ready' ],
            $this->version,
            true
        );
    }

    /**
     * Whether the current screen edits a product.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    private function is_product_edit_screen($hook_suffix)
    {
        if ('post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        return $screen && 'post' === $screen->base && function_exists('ppcart_is_product_post_type') && ppcart_is_product_post_type($screen->post_type);
    }

    /**
     * Product settings metabox layout, tabs and required-field check.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return void
     */
    private function enqueue_product_metabox_assets($hook_suffix)
    {
        if (! $this->is_product_edit_screen($hook_suffix)) {
            return;
        }

        wp_enqueue_style(
            'ppcart-components',
            PPCART_BASE_URL . 'admin/css/ppcart-components.css',
            [ 'ppcart', 'ppcart-pro-locks' ],
            $this->version,
            'all'
        );

        wp_enqueue_style(
            'ppcart-product-metabox',
            PPCART_BASE_URL . 'admin/css/ppcart-product-metabox.css',
            [ 'ppcart-components' ],
            $this->version,
            'all'
        );

        wp_enqueue_script(
            'ppcart-product-metabox',
            PPCART_BASE_URL . 'admin/js/ppcart-product-metabox.js',
            [ 'jquery', 'ppcart-repeater' ],
            $this->version,
            true
        );

        wp_localize_script('ppcart-product-metabox', 'ppcartProductMetaboxI18n', [
            'field'        => __('Field', 'publishpress-cart'),
            'required'     => __('Enter a value. This field is required.', 'publishpress-cart'),
            'oneEmpty'     => __('1 required field is empty', 'publishpress-cart'),
            /* translators: %d: number of empty required fields. */
            'manyEmpty'    => __('%d required fields are empty', 'publishpress-cart'),
            'heading'      => __('Some required fields are empty.', 'publishpress-cart'),
            'intro'        => __('Fill them in, then save the product again.', 'publishpress-cart'),
            /* translators: %d: number of fields not listed. */
            'more'         => __('and %d more', 'publishpress-cart'),
            'editorNotice' => __('Product settings: some required fields are empty.', 'publishpress-cart'),
            'showFields'   => __('Show fields', 'publishpress-cart'),
            'sale'         => __('Sale', 'publishpress-cart'),
            'hidden'       => __('Hidden', 'publishpress-cart'),
        ]);
    }
}
