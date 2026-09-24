<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Page_Shortcodes_Trait
{
    public function product_shortcode($atts)
    {
        // Elementor/Divi store content in postmeta; enqueue gated assets at render time.
        do_action('ppcart_enqueue_frontend_assets');

        $__ppcart_template_result = include __DIR__ . '/templates/page-shortcodes-ppcart-product-shortcode.php';
        return 1 === $__ppcart_template_result ? null : ppcart_kses_frontend_html($__ppcart_template_result);
    }

    public function receipt_shortcode()
    {
        $ppcart_order_get = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);
        if (false !== $ppcart_order_get && null !== $ppcart_order_get) {
            do_action('ppcart_enqueue_frontend_assets');
            return ppcart_kses_frontend_html(ppcart_get_template('shortcodes/receipt', '', ppcart_get_item_list(absint($ppcart_order_get))));
        } else {
            return;
        }
    }

    public function store_shortcode($attr)
    {
        do_action('ppcart_enqueue_frontend_assets');

        // Parse shortcode attributes
        $defaults = [ 'button_text' => __('Purchase', 'publishpress-cart'), 'purchased_text' => __('Already Purchased', 'publishpress-cart'), 'posts_per_page' => 12, 'cols' => 3];
        $attr = shortcode_atts($defaults, $attr);
        $paged = get_query_var('paged', 1);

        $args = [
            'posts_per_page'    => intval($attr['posts_per_page']),
            'paged'             => $paged,
            'post_type'         => array_merge(ppcart_query_post_types('product'), ppcart_query_pro_post_types('collection')),
        ];

        $args = apply_filters('ppcart_product_archive_args', $args);

        $the_query = new WP_Query($args);
        if ($the_query->have_posts()) {
            $attr['query'] = $the_query;
            return ppcart_kses_frontend_html(ppcart_get_template('shortcodes/archive', '', $attr));
        }
    }

    public function product_payment_plans($prod_id)
    {
        $ret = [];
        $options = ppcart_get_post_meta($prod_id, 'pay_options');
        foreach ($options as $option) {
            foreach ($option as $value) {
                if (isset($value['option_id'])) {
                    $value['option_name'] ??= $value['option_id'];
                    $ret[$value['option_id']] = $value['option_name'];
                }
            }
        }
        return $ret;
    }
}
