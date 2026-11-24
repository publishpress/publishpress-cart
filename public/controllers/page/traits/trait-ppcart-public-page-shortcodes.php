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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only receipt routing, same fallback as ppcart_do_payment_confirmation().
        if ((false === $ppcart_order_get || null === $ppcart_order_get) && isset($_GET['ppcart-order'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only receipt routing, same fallback as ppcart_do_payment_confirmation().
            $ppcart_order_get = absint(wp_unslash($_GET['ppcart-order']));
            $ppcart_order_get = $ppcart_order_get ? $ppcart_order_get : false;
        }

        // The receipt also lists download links, so it needs the same proof as the confirmation page.
        if (false !== $ppcart_order_get && null !== $ppcart_order_get && PPCart_Order::visitor_can_view(absint($ppcart_order_get))) {
            do_action('ppcart_enqueue_frontend_assets');
            return ppcart_kses_frontend_html(ppcart_get_template('shortcodes/receipt', '', ppcart_get_item_list(absint($ppcart_order_get))));
        } else {
            return;
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
