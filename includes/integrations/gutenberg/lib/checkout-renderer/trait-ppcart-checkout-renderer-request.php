<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Checkout_Renderer_Request
{
    public function can_edit_checkout_products()
    {
        return ppcart_user_can('edit_ppcart_products');
    }

    public function can_preview_checkout_product(WP_REST_Request $request)
    {
        $product_id = absint($request->get_param('pid'));

        if (! $product_id) {
            return $this->can_edit_checkout_products();
        }

        return $this->can_access_product($product_id);
    }

    public function get_products(WP_REST_Request $request)
    {
        $search = $request->get_param('search');
        $args   = [
            'post_type'              => $this->get_product_post_types(),
            'post_status'            => [ 'publish', 'draft', 'pending', 'private', 'future' ],
            'posts_per_page'         => 100,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters'       => false,
        ];

        if ('' !== $search) {
            $args['s'] = $search;
        }

        $products = get_posts($args);
        $data     = [];

        foreach ($products as $product) {
            if (! $this->can_access_product($product->ID)) {
                continue;
            }

            $data[] = [
                'id'                  => $product->ID,
                'title'               => html_entity_decode(get_the_title($product), ENT_QUOTES, get_bloginfo('charset')),
                'plans'               => $this->get_product_plans($product->ID),
                'sectionAvailability' => $this->get_product_section_availability($product->ID),
                'defaultTemplate'     => $this->get_product_default_template($product->ID),
            ];
        }

        return rest_ensure_response($data);
    }

    public function get_preview(WP_REST_Request $request)
    {
        $product_id = absint($request->get_param('pid'));

        if ($product_id && ! $this->can_access_product($product_id)) {
            return new WP_Error(
                'rest_forbidden',
                __('Sorry, you are not allowed to preview this checkout form.', 'publishpress-cart'),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        $title      = $product_id ? get_the_title($product_id) : __('Dynamic product', 'publishpress-cart');

        if (! $title) {
            $title = __('Selected product', 'publishpress-cart');
        }

        return rest_ensure_response(
            [
                'title'       => wp_strip_all_tags($title),
                'pluginTitle' => apply_filters('ppcart_plugin_title', 'PublishPress Cart'),
                'template'    => $this->normalize_template($request->get_param('template')),
                'plan'        => sanitize_text_field($request->get_param('plan')),
                'coupon'      => sanitize_text_field($request->get_param('coupon')),
                'html'        => $this->render_preview_html($request),
            ]
        );
    }

    public function render($attributes, $content = '')
    {
        $attributes = wp_parse_args($attributes, $this->get_default_attributes());
        $block_id   = $this->get_block_id($attributes);
        $shortcode  = $this->build_shortcode($attributes);

        if (! $shortcode) {
            return '';
        }

        $checkout_output = $this->render_checkout_shortcode($shortcode, $attributes);

        if ('' === trim($checkout_output)) {
            return '';
        }

        if (function_exists('get_block_wrapper_attributes')) {
            $wrapper_attributes = get_block_wrapper_attributes(
                [
                    'id'    => $block_id,
                    'class' => 'publishpress-cart-checkout-form',
                ]
            );
        } else {
            $wrapper_attributes = sprintf(
                'id="%1$s" class="%2$s"',
                esc_attr($block_id),
                esc_attr('publishpress-cart-checkout-form')
            );
        }

        $styles          = $this->build_styles($block_id, $attributes['styleSettings'] ?? []);
        $checkout_output = ppcart_kses_frontend_html($checkout_output);

        return sprintf('<div %1$s>%2$s%3$s</div>', $wrapper_attributes, $styles, $checkout_output);
    }

    private function build_shortcode($attributes, $is_preview = false)
    {
        $parts      = [ 'ppcart_form' ];
        $product_id = $this->resolve_product_id($attributes);

        if (! $product_id) {
            return '';
        }

        $parts[] = 'id="' . esc_attr($product_id) . '"';

        if (! empty($attributes['hide_labels'])) {
            $parts[] = 'hide_labels="hide"';
        }

        $template = isset($attributes['template']) ? $this->normalize_template($attributes['template']) : '';
        if ('' === $template && $product_id) {
            $template = $this->get_product_default_template($product_id);
        }
        if ('' !== $template) {
            $parts[] = 'template="' . esc_attr($template) . '"';
        }

        if (! empty($attributes['plan'])) {
            $parts[] = 'plan="' . esc_attr(sanitize_text_field($attributes['plan'])) . '"';
        }

        if (! empty($attributes['coupon'])) {
            $parts[] = 'coupon="' . esc_attr(sanitize_text_field($attributes['coupon'])) . '"';
        }

        if ($is_preview) {
            $parts[] = 'builder="true"';
        }

        return '[' . implode(' ', $parts) . ']';
    }

    private function resolve_product_id($attributes)
    {
        $product_id = isset($attributes['pid']) ? absint($attributes['pid']) : 0;

        if ($product_id) {
            return $product_id;
        }

        global $post;

        if (! $post instanceof WP_Post) {
            return 0;
        }

        $post_types = $this->get_product_post_types();

        return in_array(get_post_type($post), $post_types, true) ? absint($post->ID) : 0;
    }

    private function render_checkout_shortcode($shortcode, $attributes)
    {
        $content_order = $this->normalize_content_order($attributes['contentOrder'] ?? []);
        $text_settings = $this->normalize_text_settings($attributes['textSettings'] ?? []);
        $callback      = function ($arrangement, $context) use ($content_order, $text_settings) {
            $template = isset($context['template']) ? $this->normalize_template($context['template']) : '';

            return [
                'contentOrder' => $content_order,
                'template'     => $template,
                'textSettings' => $text_settings,
            ];
        };

        add_filter('ppcart_checkout_block_arrangement', $callback, 10, 2);
        $output = do_shortcode($shortcode);
        remove_filter('ppcart_checkout_block_arrangement', $callback, 10);

        return $output;
    }

    private function render_preview_html(WP_REST_Request $request)
    {
        $attributes = [
            'pid'         => (string) absint($request->get_param('pid')),
            'hide_labels' => rest_sanitize_boolean($request->get_param('hide_labels')),
            'template'    => $this->normalize_template($request->get_param('template')),
            'plan'        => sanitize_text_field($request->get_param('plan')),
            'coupon'      => sanitize_text_field($request->get_param('coupon')),
            'contentOrder' => $this->decode_content_order($request->get_param('content_order')),
            'textSettings' => $this->decode_text_settings($request->get_param('text_settings')),
        ];

        if (! absint($attributes['pid'])) {
            return '';
        }

        $shortcode = $this->build_shortcode($attributes, true);

        if (! $shortcode) {
            return '';
        }

        $block_id = wp_unique_id('ppcart-checkout-preview-');

        $styles          = $this->build_styles($block_id, $this->decode_style_settings($request->get_param('style_settings')));
        $checkout_output = ppcart_kses_frontend_html($this->render_checkout_shortcode($shortcode, $attributes));

        return sprintf('<div id="%1$s">%2$s%3$s</div>', esc_attr($block_id), $styles, $checkout_output);
    }

    private function normalize_template($template)
    {
        $template = sanitize_key((string) $template);

        if ('true' === $template || 'yes' === $template) {
            return '2-step';
        }

        $allowed = [ '', 'normal', '2-step', 'opt-in', 'split-in' ];

        return in_array($template, $allowed, true) ? $template : '';
    }

    private function get_product_default_template($product_id)
    {
        if (ppcart_get_post_meta($product_id, 'show_2_step', true)) {
            return '2-step';
        }

        $template = ppcart_get_post_meta($product_id, 'display', true);

        if ('two_step' === $template) {
            return '2-step';
        }

        if ('opt_in' === $template) {
            return 'opt-in';
        }

        if ('split_in' === $template) {
            return 'split-in';
        }

        return $this->normalize_template($template);
    }

    private function get_product_plans($product_id)
    {
        $plans   = [];
        $options = ppcart_get_post_meta($product_id, 'pay_options');

        foreach ($options as $option_group) {
            if (! is_array($option_group)) {
                continue;
            }

            foreach ($option_group as $option) {
                if (! is_array($option) || empty($option['option_id'])) {
                    continue;
                }

                $plans[] = [
                    'value' => sanitize_text_field($option['option_id']),
                    'label' => isset($option['option_name']) && '' !== $option['option_name'] ? sanitize_text_field($option['option_name']) : sanitize_text_field($option['option_id']),
                ];
            }
        }

        return $plans;
    }

    private function get_product_section_availability($product_id)
    {
        global $ppcart_stripe;

        $product = function_exists('ppcart_setup_product') ? ppcart_setup_product($product_id) : null;
        $plans   = $this->get_product_plans($product_id);

        $payment_methods = [];
        if (apply_filters('ppcart_checkout_payment_method_enabled', true, 'stripe', $product_id)) {
            if (get_option('_ppcart_stripe_enable') == '1' && is_array($ppcart_stripe)) {
                $payment_methods['stripe'] = [
                    'value'        => 'stripe',
                    'label'        => esc_html__('Credit Card', 'publishpress-cart'),
                    'single_label' => false,
                ];
            }
        }
        if (apply_filters('ppcart_checkout_payment_method_enabled', true, 'cashondelivery', $product_id)) {
            if (get_option('_ppcart_cashondelivery_enable') == '1') {
                $payment_methods['cashondelivery'] = [
                    'value' => 'cod',
                    'label' => esc_html__('Cash on Delivery', 'publishpress-cart'),
                ];
            }
        }
        $payment_methods = apply_filters('ppcart_payment_methods', $payment_methods, $product_id);

        $sections = [
            'payment_plan'   => $product ? empty($product->hide_plans) && count($plans) > 1 : count($plans) > 1,
            'coupon'         => false,
            'contact_info'   => true,
            'payment_method' => ! empty($payment_methods),
            'payment_details' => is_array($ppcart_stripe),
            'order_bumps'    => false,
            'order_summary'  => true,
            'terms_consent'  => $product && (! empty($product->terms_url) || ! empty($product->privacy_url) || ! empty($product->show_optin_cb)),
            'express_payment' => is_array($ppcart_stripe) && ! empty($ppcart_stripe['is_express_payment']),
            'submit_button'  => true,
        ];

        return apply_filters('ppcart_checkout_block_sections', $sections, $product_id, $product);
    }

    private function can_access_product($product_id)
    {
        $product_id = absint($product_id);
        $post       = $product_id ? get_post($product_id) : null;

        if (! $post || ! post_type_exists($post->post_type)) {
            return false;
        }

        if (! current_user_can('edit_post', $product_id)) {
            return false;
        }

        return in_array($post->post_type, $this->get_product_post_types(), true);
    }

    public function get_product_post_types()
    {
        if (function_exists('ppcart_filtered_product_post_types')) {
            return ppcart_filtered_product_post_types();
        }

        $post_types = (array) apply_filters('ppcart_product_post_type', 'ppcart_product');
        $post_types = array_filter(array_map('sanitize_key', $post_types));

        return $post_types ? array_values(array_unique($post_types)) : [ 'ppcart_product' ];
    }

    private function get_block_id($attributes)
    {
        if (! empty($attributes['anchor'])) {
            $anchor = trim(sanitize_text_field($attributes['anchor']));

            if ('' !== $anchor) {
                return $anchor;
            }
        }

        return wp_unique_id('ppcart-checkout-');
    }
}
