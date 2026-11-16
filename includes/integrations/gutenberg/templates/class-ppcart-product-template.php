<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registers the default block-theme template for PublishPress Cart products.
 */
class PPCart_Product_Template
{
    public const PLUGIN_SLUG         = 'publishpress-cart';
    public const TEMPLATE_SLUG       = 'single-ppcart_product';
    public const TEMPLATE_NAME       = 'publishpress-cart//single-ppcart_product';
    public const FALLBACK_IMAGE_PATH = 'public/images/checkout-background.webp';

    public function __construct()
    {
        add_action('init', [ $this, 'register' ], 1000);
        add_filter('render_block_core/group', [$this, 'render_product_header'], 10, 2);
        add_filter('render_block_core/post-title', [$this, 'render_product_header'], 10, 2);
        add_filter('render_block_core/cover', [ $this, 'render_cover_fallback_image' ], 10, 2);
    }

    public static function is_supported_block_theme()
    {
        if (! function_exists('register_block_template') || ! function_exists('wp_is_block_theme')) {
            return false;
        }

        if (! wp_is_block_theme()) {
            return false;
        }

        $enabled = true;

        return (bool) apply_filters('ppcart_use_block_product_template', $enabled);
    }

    public function register()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-product-template-register.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function render_cover_fallback_image($block_content, $block)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-template-render-cover-fallback-image.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function render_product_header($content, $block)
    {
        $class = $block['attrs']['className'] ?? '';
        if (! in_array($class, ['ppcart-product-header', 'ppcart-product-header-title'], true)) {
            return $content;
        }

        $id = get_queried_object_id();
        if (! $id || ! is_singular(self::get_product_post_types())) {
            return $content;
        }

        if ('ppcart-product-header-title' === $class) {
            return ppcart_get_post_meta($id, 'hide_title', true) ? '' : $content;
        }

        $color = sanitize_hex_color(ppcart_get_post_meta($id, 'header_color', true));
        $image = esc_url_raw((string) ppcart_get_post_meta($id, 'header_image', true));
        if (! $color && ! $image) {
            return $content;
        }

        $html = new WP_HTML_Tag_Processor($content);
        if (! $html->next_tag(['class_name' => 'ppcart-product-header'])) {
            return $content;
        }

        $style = (string) $html->get_attribute('style');
        if ($color) {
            $style .= ';background-color:' . $color;
        }
        if ($image) {
            $style .= ';background-image:url("' . str_replace(['"', '(', ')'], ['%22', '%28', '%29'], $image) . '");background-size:cover;background-position:center';
        }
        $style .= ';padding:var(--wp--preset--spacing--40, 24px)';
        $html->set_attribute('style', safecss_filter_attr($style));

        return $html->get_updated_html();
    }

    public static function get_product_post_types()
    {
        if (function_exists('ppcart_filtered_product_post_types')) {
            return ppcart_filtered_product_post_types();
        }

        $post_types = (array) apply_filters('ppcart_product_post_type', 'ppcart_product');
        $post_types = array_filter(array_map('sanitize_key', $post_types));

        return $post_types ? array_values(array_unique($post_types)) : [ 'ppcart_product' ];
    }

    private static function get_template_name($post_type)
    {
        return self::get_registered_template_name($post_type);
    }

    /**
     * Block template name for a product CPT slug (`plugin//single-{post_type}`).
     *
     * @param string $post_type Product post type. Empty uses the live product slug.
     * @return string
     */
    public static function get_registered_template_name($post_type = '')
    {
        if ('' === $post_type) {
            $post_type = function_exists('ppcart_live_post_type')
                ? ppcart_live_post_type('product')
                : 'ppcart_product';
        }

        return self::PLUGIN_SLUG . '//single-' . sanitize_key((string) $post_type);
    }

    public static function get_template_title($post_type)
    {
        if (function_exists('ppcart_is_pro_post_type') && ppcart_is_pro_post_type('collection', $post_type)) {
            return esc_html__('Single Collection', 'publishpress-cart');
        }

        return esc_html__('Single Product', 'publishpress-cart');
    }

    public static function get_fallback_image_url()
    {
        if (! defined('PPCART_BASE_DIR') || ! defined('PPCART_BASE_URL')) {
            return '';
        }

        $fallback_image_path = trailingslashit(PPCART_BASE_DIR) . self::FALLBACK_IMAGE_PATH;
        if (! file_exists($fallback_image_path)) {
            return '';
        }

        return trailingslashit(PPCART_BASE_URL) . self::FALLBACK_IMAGE_PATH;
    }

    public static function get_template_content()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/product-template-get-template-content.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
