<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metaboxes_Render_Trait
{
    /**
         * Registers metaboxes with WordPress
         *
         * @since 1.0.0
         * @access public
         */
    public function add_metaboxes()
    {

        $post_type = (array) apply_filters('ppcart_product_metabox_post_type', ppcart_live_post_type('product'));
        if ([] === $post_type) {
            $post_type = ppcart_query_post_types('product');
        }

        foreach ($post_type as $type) {
            add_meta_box(
                'ppcart-product-settings',
                apply_filters($this->plugin_name . '-metabox-title-product-settings', esc_html__('PublishPress Cart', 'publishpress-cart')),
                [ $this, 'product_settings_fields' ],
                $type,
                'normal',
                'default'
            );
        }
    }

    /**
         * Outputs the one shared preview/test modal, populated by the repeater JS from the active row.
         *
         * @return void
         */
    private function render_product_notification_modal()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-render-render-product-notification-modal.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function product_settings_fields($post, $params)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-render-product-settings-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Returns the ordered product settings tabs.
         *
         * @return array<string,string>
         */
    private function get_product_setting_tabs()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-render-get-product-setting-tabs.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Returns the ordered product setting field groups for rendering or saving.
         *
         * @param string $context Current field use context.
         * @return array<int,string>
         */
    private function get_product_field_groups($context = 'render')
    {
        $groups = [ 'general', 'access', 'payments', 'pricing', 'fields', 'confirmation', 'notifications', 'integrations' ];

        return apply_filters('ppcart_product_field_groups', $groups, $context);
    }

    /**
         * Applies product tab field filters.
         *
         * @param string $tab_id  Product settings tab ID.
         * @param array  $fields  Tab fields.
         * @param string $context Current field use context.
         * @return array
         */
    private function filter_product_setting_tab_fields($tab_id, $fields, $context = 'render')
    {
        $fields = apply_filters("ppcart_product_setting_tab_{$tab_id}_fields", $fields);
        $fields = apply_filters("ppcart_product_{$tab_id}_fields", $fields);

        return apply_filters('ppcart_product_setting_tab_fields', $fields, $tab_id, $context);
    }

    private function metabox_fields($fields)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-metabox-render-metabox-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
