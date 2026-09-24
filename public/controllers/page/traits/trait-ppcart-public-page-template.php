<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Page_Template_Trait
{
    public function email_preview_template($template)
    {
        $preview_nonce = ppcart_filter_input_request('_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $email_preview_template = PPCART_BASE_DIR . 'public/templates/email/preview.php';

        if (
            (('email' === get_query_var('ppcart-preview') && current_user_can('edit_posts')))
            && file_exists($email_preview_template)
            && (is_string($preview_nonce) && ppcart_verify_nonce(sanitize_text_field($preview_nonce), 'ppcart_cart'))
        ) {
            require_once $email_preview_template;
            exit;
        }
    }

    public function product_template($single)
    {
        global $post;

        if (! $post instanceof WP_Post) {
            return $single;
        }

        /* Checks for single template by post type */
        $post_type = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
        if (in_array($post->post_type, $post_type)) {
            $legacy_template = $this->get_legacy_product_template();

            if ($this->is_order_confirmation_request() && $legacy_template) {
                return $legacy_template;
            }

            $page_template = ppcart_get_post_meta($post->ID, 'page_template', true);
            if ($page_template || get_option('_ppcart_disable_template')) {
                return $single;
            }

            if (
                class_exists('PPCart_Product_Template')
                && PPCart_Product_Template::is_supported_block_theme()
            ) {
                return $single;
            }

            if ($legacy_template) {
                return $legacy_template;
            }
        }

        return $single;
    }

    protected function is_order_confirmation_request()
    {
        $ppcart_order_get = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);

        return false !== $ppcart_order_get && null !== $ppcart_order_get && absint($ppcart_order_get) > 0;
    }

    private function get_legacy_product_template()
    {
        $template = PPCART_BASE_DIR . 'public/templates/checkout1.php';

        return file_exists($template) ? $template : '';
    }

    public function public_product_name($title, $id = null)
    {
        $pid = false;
        if (!$id) {
            $pid = get_the_ID();
        }

        switch ($id) {
            case $pid:
                $id = $pid;
                break;
            case is_object($id):
                if (is_object($id)) {
                    $id = $id->ID;
                }
                break;
        }
        if (!is_admin()) {
            $post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
            if (in_array(get_post_type($id), $post_types) && $name = ppcart_get_post_meta($id, 'product_name', true)) {
                return esc_html(ppcart_normalize_product_name($name));
            }
        }
        return $title;
    }
}
