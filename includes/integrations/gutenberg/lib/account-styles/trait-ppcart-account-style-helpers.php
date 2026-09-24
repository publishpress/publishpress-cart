<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Account_Style_Helpers
{
    private function append_color_style_property(&$style_properties, $attributes, $attribute_name, $css_variable)
    {
        $color = isset($attributes[ $attribute_name ]) ? sanitize_hex_color($attributes[ $attribute_name ]) : '';

        if ($color) {
            $style_properties[] = $css_variable . ':' . $color;
        }
    }

    private function get_layout_inner_layout_attribute($attributes)
    {
        return $this->get_choice_attribute($attributes, 'innerLayout', [ '', 'left', 'center', 'right', 'stretch' ], '');
    }

    private function get_layout_width_attribute($attributes)
    {
        $unit     = $this->get_layout_width_unit($attributes);
        $settings = $this->get_layout_width_unit_settings($unit);
        $width    = $this->get_float_attribute($attributes, 'contentWidth', $settings['min'], $settings['max']);

        return null === $width ? null : $this->format_css_number($width) . $unit;
    }

    private function get_layout_width_unit($attributes)
    {
        $unit = isset($attributes['contentWidthUnit']) ? strtolower(trim((string) $attributes['contentWidthUnit'])) : 'px';

        return in_array($unit, [ 'px', '%', 'em', 'rem', 'vw', 'vh' ], true) ? $unit : 'px';
    }

    private function get_layout_width_unit_settings($unit)
    {
        $settings = [
            'px'  => [
                'min' => 320,
                'max' => 1600,
            ],
            '%'   => [
                'min' => 1,
                'max' => 100,
            ],
            'em'  => [
                'min' => 1,
                'max' => 120,
            ],
            'rem' => [
                'min' => 1,
                'max' => 120,
            ],
            'vw'  => [
                'min' => 1,
                'max' => 100,
            ],
            'vh'  => [
                'min' => 1,
                'max' => 100,
            ],
        ];

        return $settings[ $unit ] ?? $settings['px'];
    }

    private function get_choice_attribute($attributes, $attribute_name, $allowed_values, $default)
    {
        $value = isset($attributes[ $attribute_name ]) ? sanitize_key($attributes[ $attribute_name ]) : $default;

        return in_array($value, $allowed_values, true) ? $value : $default;
    }

    public function get_detail_presentation($attributes, $block_name = '')
    {
        $allowed_values = [ '', 'slide-right', 'slide-left', 'slide-down' ];

        return $this->get_choice_attribute($attributes, 'detailPresentation', $allowed_values, '');
    }

    private function get_float_attribute($attributes, $attribute_name, $minimum, $maximum)
    {
        if (! isset($attributes[ $attribute_name ]) || '' === $attributes[ $attribute_name ] || ! is_numeric($attributes[ $attribute_name ])) {
            return null;
        }

        $value = abs((float) $attributes[ $attribute_name ]);

        return max($minimum, min($maximum, $value));
    }

    private function format_css_number($value)
    {
        $number = (float) $value;

        if (floor($number) === $number) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }

    private function get_number_attribute($attributes, $attribute_name, $minimum, $maximum)
    {
        if (! isset($attributes[ $attribute_name ]) || '' === $attributes[ $attribute_name ] || ! is_numeric($attributes[ $attribute_name ])) {
            return null;
        }

        $value = absint($attributes[ $attribute_name ]);

        return max($minimum, min($maximum, $value));
    }


    private function get_block_id($attributes, $prefix = 'ppcart-account-')
    {
        if (! empty($attributes['anchor'])) {
            $anchor = trim(sanitize_text_field($attributes['anchor']));

            if ('' !== $anchor) {
                return $anchor;
            }
        }

        return wp_unique_id($prefix);
    }
}
