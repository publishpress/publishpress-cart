<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Checkout_Renderer_Style_Utilities
{
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
