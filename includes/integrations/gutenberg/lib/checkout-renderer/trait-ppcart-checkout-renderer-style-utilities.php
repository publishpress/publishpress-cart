<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Checkout_Renderer_Style_Utilities
{
    private function get_style_preset_defaults($preset)
    {
        $presets = [
            'minimal' => [
                'density'      => 'compact',
                'cornerRadius' => 'square',
            ],
            'carded' => [
                'surfaceStyle' => 'card',
                'cornerRadius' => 'rounded',
            ],
            'compact' => [
                'surfaceStyle' => 'boxed',
                'density'      => 'compact',
                'cornerRadius' => 'square',
            ],
            'contrast' => [
                'accentColor'  => '#111827',
                'surfaceStyle' => 'boxed',
                'cornerRadius' => 'square',
            ],
        ];

        return $presets[ $preset ] ?? [];
    }

    private function build_style_rule($block_id, $selector, $declarations)
    {
        $rule = '';

        foreach ($declarations as $property => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            $declaration = $this->build_declaration($property, $value);

            if ($declaration) {
                $rule .= rtrim($declaration, ';') . ';';
            }
        }

        if ('' === $rule) {
            return '';
        }

        return $this->prefix_selector($block_id, $selector) . '{' . $rule . '}';
    }

    private function get_density_values($density)
    {
        $values = [
            'compact' => [
                'wrapper_padding' => '16px',
                'section_gap'     => '10px',
                'field_padding'   => '8px 10px',
                'option_padding'  => '10px',
                'summary_padding' => '10px 12px',
            ],
            'spacious' => [
                'wrapper_padding' => '32px',
                'section_gap'     => '20px',
                'field_padding'   => '14px 16px',
                'option_padding'  => '16px',
                'summary_padding' => '16px 18px',
            ],
        ];

        if (isset($values[ $density ])) {
            return $values[ $density ];
        }

        return [
            'wrapper_padding' => '24px',
            'section_gap'     => '14px',
            'field_padding'   => '10px 12px',
            'option_padding'  => '12px',
            'summary_padding' => '12px 14px',
        ];
    }

    private function get_style_radius($radius)
    {
        if ('square' === $radius) {
            return '0';
        }

        if ('rounded' === $radius) {
            return '10px';
        }

        return '4px';
    }

    private function get_field_height($density)
    {
        if ('compact' === $density) {
            return '38px';
        }

        if ('spacious' === $density) {
            return '52px';
        }

        return '44px';
    }

    private function get_button_padding($density)
    {
        if ('spacious' === $density) {
            return '15px 20px';
        }

        if ('compact' === $density) {
            return '10px 14px';
        }

        return '12px 18px';
    }

    private function normalize_hex_color($color)
    {
        $color = trim((string) $color);

        if (! preg_match('/^#?([a-f0-9]{3}|[a-f0-9]{6})$/i', $color, $matches)) {
            return '';
        }

        $hex = strtolower($matches[1]);

        if (3 === strlen($hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return '#' . $hex;
    }

    private function mix_hex_color($color, $target, $weight, $fallback)
    {
        $color  = $this->normalize_hex_color($color);
        $target = $this->normalize_hex_color($target);

        if (! $color || ! $target) {
            return $fallback;
        }

        $color_rgb  = sscanf($color, '#%02x%02x%02x');
        $target_rgb = sscanf($target, '#%02x%02x%02x');
        $mixed      = [];

        foreach ($color_rgb as $index => $channel) {
            $mixed[] = (int) round($channel * (1 - $weight) + $target_rgb[ $index ] * $weight);
        }

        return sprintf('#%02x%02x%02x', $mixed[0], $mixed[1], $mixed[2]);
    }

    private function build_declaration($property, $value)
    {
        $property = sanitize_key($property);
        $value    = trim(sanitize_text_field($value));

        if ('box-shadow' === $property && preg_match('/^[a-z0-9#.,%()\s-]+$/i', $value)) {
            return esc_html($property . ': ' . $value);
        }

        if ('display' === $property && 'flex' === $value) {
            return esc_html($property . ': ' . $value);
        }

        $declaration = safecss_filter_attr($property . ': ' . $value . ';');

        return $declaration ? esc_html($declaration) : '';
    }

    private function prefix_selector($block_id, $selector)
    {
        $selectors = array_map('trim', explode(',', $selector));
        $prefixed  = [];
        $block_id  = $this->escape_css_identifier($block_id);

        foreach ($selectors as $single_selector) {
            if ('' !== $single_selector) {
                $prefixed[] = '#' . $block_id . ' ' . $single_selector;
            }
        }

        return implode(',', $prefixed);
    }

    private function escape_css_identifier($identifier)
    {
        $identifier = (string) $identifier;
        $escaped    = '';
        $length     = strlen($identifier);

        for ($index = 0; $index < $length; $index++) {
            $character = $identifier[ $index ];
            $is_digit  = preg_match('/[0-9]/', $character);
            $is_safe   = preg_match('/[a-zA-Z0-9_-]/', $character);

            if (
                ! $is_safe
                || (0 === $index && $is_digit)
                || (1 === $index && '-' === $identifier[0] && $is_digit)
            ) {
                $escaped .= '\\' . dechex(ord($character)) . ' ';
                continue;
            }

            $escaped .= $character;
        }

        return $escaped;
    }
}
