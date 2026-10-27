<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Appearance of an embedded checkout, as saved in the product's `_ppcart_embed_*` meta.
 *
 * Pure PHP with no WordPress calls, so unit tests run without WordPress. Every value is
 * optional and '' means "keep the current Cart look", so the static stylesheet
 * `public/css/ppcart-embed.css` scopes each rule by the matching body class and an unset
 * value writes no rule.
 */
class PPCart_Embed_Appearance
{
    const KEYS = [ 'layout', 'accent', 'text', 'background', 'font_family', 'font_url', 'radius', 'button_text' ];

    const LAYOUTS = [ 'standard', '2-step' ];

    const RADIUS_MAX = 24;

    const FONT_FAMILY_MAX = 200;

    const BUTTON_TEXT_MAX = 60;

    /**
     * Keys that become a body class and a CSS variable, with the suffix both share.
     */
    const STYLE_SUFFIXES = [
        'accent'      => 'accent',
        'text'        => 'text',
        'background'  => 'bg',
        'font_family' => 'font',
        'radius'      => 'radius',
    ];

    /**
     * @param mixed $raw Short keys to raw values. Unknown keys are dropped. Never throws.
     * @return array<string, string|int> All eight keys; each invalid value is ''.
     */
    public static function sanitize($raw)
    {
        $raw   = is_array($raw) ? $raw : [];
        $clean = [];

        foreach (self::KEYS as $key) {
            $clean[ $key ] = self::sanitize_value($key, isset($raw[ $key ]) ? $raw[ $key ] : '');
        }

        return $clean;
    }

    /**
     * @param array<string, string|int> $appearance Output of sanitize().
     * @return array<int, string> One class per set value, plus the 2-step layout class.
     */
    public static function body_classes(array $appearance)
    {
        $classes = [];

        foreach (self::STYLE_SUFFIXES as $key => $suffix) {
            if (self::is_set($appearance, $key)) {
                $classes[] = 'ppcart-embed--' . $suffix;
            }
        }

        if (isset($appearance['layout']) && '2-step' === $appearance['layout']) {
            $classes[] = 'ppcart-embed--layout-2-step';
        }

        return $classes;
    }

    /**
     * @param array<string, string|int> $appearance Output of sanitize().
     * @return string Custom property declarations for the set values only.
     */
    public static function css_variables(array $appearance)
    {
        $css = '';

        foreach (self::STYLE_SUFFIXES as $key => $suffix) {
            if (! self::is_set($appearance, $key)) {
                continue;
            }

            $value = 'radius' === $key ? (int) $appearance[ $key ] . 'px' : $appearance[ $key ];
            $css  .= '--ppcart-embed-' . $suffix . ':' . $value . ';';
        }

        return $css;
    }

    private static function is_set(array $appearance, $key)
    {
        return isset($appearance[ $key ]) && '' !== $appearance[ $key ];
    }

    private static function sanitize_value($key, $value)
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);

        if ('' === $value) {
            return '';
        }

        switch ($key) {
            case 'layout':
                return in_array($value, self::LAYOUTS, true) ? $value : '';

            case 'accent':
            case 'text':
            case 'background':
                return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : '';

            case 'font_family':
                return preg_match('/^[A-Za-z0-9 ,\'"-]{1,' . self::FONT_FAMILY_MAX . '}$/', $value) ? $value : '';

            case 'font_url':
                return self::sanitize_font_url($value);

            case 'radius':
                return preg_match('/^[0-9]{1,2}$/', $value) && (int) $value <= self::RADIUS_MAX ? (int) $value : '';

            case 'button_text':
                $value = trim(strip_tags($value));

                return ('' !== $value && self::length($value) <= self::BUTTON_TEXT_MAX) ? $value : '';
        }

        return '';
    }

    private static function sanitize_font_url($value)
    {
        if (0 !== strpos($value, 'https://') || preg_match('/[\s\'"<>]/', $value)) {
            return '';
        }

        return false === filter_var($value, FILTER_VALIDATE_URL) ? '' : $value;
    }

    private static function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
