<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Checkout_Renderer_Styles
{
    private function build_styles($block_id, $settings)
    {
        $css = $this->build_base_styles($block_id);

        if (is_array($settings) && ! empty($settings)) {
            $settings = $this->normalize_style_settings($settings);
            $css     .= $this->build_design_styles($block_id, $settings);
        }

        if ('' === $css) {
            return '';
        }

        if (function_exists('ppcart_enqueue_or_print_inline_style')) {
            ppcart_enqueue_or_print_inline_style(
                PPCart_Checkout_Renderer::STYLE_HANDLE,
                $css,
                PPCart_Checkout_Renderer::STYLE_HANDLE . '-inline'
            );
        }

        return '';
    }

    private function build_base_styles($block_id)
    {
        $css = '';

        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart .total',
            [
                'align-items'     => 'center',
                'display'         => 'flex',
                'gap'             => '12px',
                'justify-content' => 'space-between',
            ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart .total .total-rhs',
            [
                'align-items'    => 'flex-end',
                'display'        => 'flex',
                'flex-direction' => 'column',
                'margin-left'    => 'auto',
                'text-align'     => 'right',
            ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart .total .price',
            [ 'float' => 'none' ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart .total small',
            [
                'clear'         => 'none',
                'margin-bottom' => '0',
                'position'      => 'static',
                'top'           => 'auto',
            ]
        );

        return $css;
    }

    private function decode_style_settings($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || '' === trim($value)) {
            return [];
        }

        $decoded = json_decode(wp_unslash($value), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decode_content_order($value)
    {
        if (is_array($value)) {
            return $this->normalize_content_order($value);
        }

        if (! is_string($value) || '' === trim($value)) {
            return [];
        }

        $decoded = json_decode(wp_unslash($value), true);

        return $this->normalize_content_order(is_array($decoded) ? $decoded : []);
    }

    private function decode_text_settings($value)
    {
        if (is_array($value)) {
            return $this->normalize_text_settings($value);
        }

        if (! is_string($value) || '' === trim($value)) {
            return [];
        }

        $decoded = json_decode(wp_unslash($value), true);

        return $this->normalize_text_settings(is_array($decoded) ? $decoded : []);
    }

    private function get_default_content_order()
    {
        return [
            'payment_plan',
            'coupon',
            'contact_info',
            'payment_method',
            'payment_details',
            'order_bumps',
            'order_summary',
            'terms_consent',
            'express_payment',
            'submit_button',
        ];
    }

    private function normalize_content_order($content_order)
    {
        if (! is_array($content_order) || empty($content_order)) {
            $content_order = [];
        }

        if (empty($content_order)) {
            return [];
        }

        $known_sections = $this->get_default_content_order();
        $normalized     = [];

        foreach ($content_order as $section_id) {
            $section_id = sanitize_key($section_id);

            if (in_array($section_id, $known_sections, true) && ! in_array($section_id, $normalized, true)) {
                $normalized[] = $section_id;
            }
        }

        foreach ($known_sections as $section_id) {
            if (! in_array($section_id, $normalized, true)) {
                $normalized[] = $section_id;
            }
        }

        return $normalized;
    }

    private function normalize_style_settings($settings)
    {
        $allowed_tokens = [
            'preset'       => [ 'minimal', 'carded', 'compact', 'contrast' ],
            'surfaceStyle' => [ 'card', 'boxed' ],
            'density'      => [ 'compact', 'spacious' ],
            'cornerRadius' => [ 'square', 'rounded' ],
        ];
        $normalized = [];

        foreach ($settings as $key => $value) {
            if ('accentColor' === $key) {
                $value = $this->normalize_hex_color($value);

                if ('' !== $value) {
                    $normalized[ $key ] = $value;
                }

                continue;
            }

            if (isset($allowed_tokens[ $key ])) {
                $value = sanitize_key($value);

                if (in_array($value, $allowed_tokens[ $key ], true)) {
                    $normalized[ $key ] = $value;
                }
            }
        }

        return $normalized;
    }

    private function normalize_text_settings($settings)
    {
        if (! is_array($settings)) {
            return [];
        }

        $allowed_keys = [
            'contactInfoHeading',
            'paymentPlanHeading',
            'paymentInfoHeading',
            'orderSummaryHeading',
            'orderTotalHeading',
            'dueTodayLabel',
            'amountDueLabel',
            'twoStepTabOneHeading',
            'twoStepTabOneSubheading',
            'twoStepTabTwoHeading',
            'twoStepTabTwoSubheading',
            'splitSiteHeading',
            'splitFormHeading',
        ];
        $normalized = [];

        foreach ($allowed_keys as $key) {
            if (! isset($settings[ $key ])) {
                continue;
            }

            $normalized[ $key ] = trim(sanitize_text_field($settings[ $key ]));
        }

        return $normalized;
    }

    private function build_design_styles($block_id, $settings)
    {
        $token_keys = [
            'preset',
            'accentColor',
            'surfaceStyle',
            'density',
            'cornerRadius',
        ];
        $has_token_settings = false;

        foreach ($token_keys as $token_key) {
            if (! empty($settings[ $token_key ])) {
                $has_token_settings = true;
                break;
            }
        }

        if (! $has_token_settings) {
            return '';
        }

        $raw_settings   = $settings;
        $preset         = $settings['preset'] ?? '';
        $settings       = array_merge($this->get_style_preset_defaults($preset), $settings);
        $accent         = ! empty($settings['accentColor']) ? $settings['accentColor'] : '#2271b1';
        $accent_hover   = $this->mix_hex_color($accent, '#000000', 0.12, '#135e96');
        $accent_soft    = $this->mix_hex_color($accent, '#ffffff', 0.88, '#eef6fc');
        $radius         = $this->get_style_radius($settings['cornerRadius'] ?? '');
        $density        = $settings['density'] ?? '';
        $density_values = $this->get_density_values($density);
        $css            = '';

        $field_selector        = '.ppcart input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]), .ppcart .StripeElement, .ppcart select.ppcart-form-control, .ppcart .selectize-control.single .selectize-input, .ppcart textarea';
        $button_selector       = '.ppcart-shortcode input[type="submit"], .ppcart-shortcode button, .ppcart-shortcode .ppcart-btn-block';
        $button_hover_selector = '.ppcart-shortcode input[type="submit"]:hover, .ppcart-shortcode button:hover, .ppcart-shortcode .ppcart-btn-block:hover';

        $css .= $this->build_surface_style_rules($block_id, $settings, $radius, $density_values);

        if (! empty($raw_settings['density']) || ! empty($raw_settings['preset'])) {
            $css .= $this->build_style_rule(
                $block_id,
                '.ppcart-shortcode .ppcart-section, .ppcart-shortcode .ppcart-form-group',
                [ 'margin-bottom' => $density_values['section_gap'] ]
            );
        }

        if (! empty($raw_settings['density']) || ! empty($raw_settings['cornerRadius']) || ! empty($raw_settings['preset'])) {
            $css .= $this->build_density_style_rules($block_id, $field_selector, $button_selector, $radius, $density, $density_values);
        }

        if (! empty($raw_settings['accentColor']) || ! empty($raw_settings['preset'])) {
            $css .= $this->build_accent_style_rules($block_id, $button_selector, $button_hover_selector, $accent, $accent_hover, $accent_soft);
        }

        return $css;
    }

    private function build_surface_style_rules($block_id, $settings, $radius, $density_values)
    {
        if (empty($settings['surfaceStyle'])) {
            return '';
        }

        if ('card' === $settings['surfaceStyle']) {
            return $this->build_style_rule(
                $block_id,
                '.ppcart-shortcode',
                [
                    'background-color' => '#ffffff',
                    'border'           => '1px solid #e5e7eb',
                    'border-radius'    => $radius,
                    'box-shadow'       => '0 16px 40px rgba(17, 24, 39, 0.08)',
                    'padding'          => $density_values['wrapper_padding'],
                ]
            );
        }

        if ('boxed' === $settings['surfaceStyle']) {
            return $this->build_style_rule(
                $block_id,
                '.ppcart-shortcode',
                [
                    'background-color' => '#f8fafc',
                    'border'           => '1px solid #dcdcde',
                    'border-radius'    => $radius,
                    'padding'          => $density_values['wrapper_padding'],
                ]
            );
        }

        return '';
    }

    private function build_density_style_rules($block_id, $field_selector, $button_selector, $radius, $density, $density_values)
    {
        $css = '';

        $css .= $this->build_style_rule(
            $block_id,
            $field_selector,
            [
                'border-radius' => $radius,
                'height'        => $this->get_field_height($density),
                'padding'       => $density_values['field_padding'],
            ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart-shortcode .products .item',
            [
                'border-radius' => $radius,
                'padding'       => $density_values['option_padding'],
            ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart-shortcode .total',
            [
                'border-radius' => $radius,
                'padding'       => $density_values['summary_padding'],
            ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            $button_selector,
            [
                'border-radius' => $radius,
                'padding'       => $this->get_button_padding($density),
            ]
        );

        return $css;
    }

    private function build_accent_style_rules($block_id, $button_selector, $button_hover_selector, $accent, $accent_hover, $accent_soft)
    {
        $css = '';

        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart-shortcode .ppcart-checkout-form-steps .steps.ppcart-current',
            [
                'background-color' => $accent_soft,
                'border-color'     => $accent,
                'color'            => $accent,
            ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart-shortcode .ppcart-checkout-form-steps .steps.ppcart-current a .step-number',
            [ 'color' => $accent ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart #ppcart-payment-form input[type="radio"] + label:after',
            [ 'background-color' => $accent ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            '.ppcart #ppcart-payment-form input[type="checkbox"] + label:after',
            [ 'border-color' => $accent ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            $button_selector,
            [
                'background-color' => $accent,
                'border-color'     => $accent,
                'color'            => '#ffffff',
            ]
        );
        $css .= $this->build_style_rule(
            $block_id,
            $button_hover_selector,
            [
                'background-color' => $accent_hover,
                'border-color'     => $accent_hover,
                'color'            => '#ffffff',
            ]
        );

        return $css;
    }
}
