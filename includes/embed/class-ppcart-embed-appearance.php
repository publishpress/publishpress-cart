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

    /*
     * Validation patterns without delimiters, so the admin live preview can build the
     * same JavaScript RegExp and preview an invalid value as the default.
     */
    const COLOR_PATTERN = '^#[0-9a-fA-F]{6}$';

    const FONT_FAMILY_PATTERN = '^[A-Za-z0-9 ,\'"-]{1,' . self::FONT_FAMILY_MAX . '}$';

    const FONT_URL_FORBIDDEN_PATTERN = '[\s\'"<>]';

    const RADIUS_PATTERN = '^[0-9]{1,2}$';

    const CLASS_PREFIX = 'ppcart-embed--';

    const VARIABLE_PREFIX = '--ppcart-embed-';

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
     * The body class and CSS variable of each styled key. body_classes(), css_variables()
     * and the admin live preview all read this map.
     *
     * @return array<string, array{class: string, variable: string, unit: string}>
     */
    public static function style_map()
    {
        $map = [];

        foreach (self::STYLE_SUFFIXES as $key => $suffix) {
            $map[ $key ] = [
                'class'    => self::CLASS_PREFIX . $suffix,
                'variable' => self::VARIABLE_PREFIX . $suffix,
                'unit'     => 'radius' === $key ? 'px' : '',
            ];
        }

        return $map;
    }

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

        foreach (self::style_map() as $key => $style) {
            if (self::is_set($appearance, $key)) {
                $classes[] = $style['class'];
            }
        }

        if (isset($appearance['layout']) && '2-step' === $appearance['layout']) {
            $classes[] = self::CLASS_PREFIX . 'layout-2-step';
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

        foreach (self::style_map() as $key => $style) {
            if (! self::is_set($appearance, $key)) {
                continue;
            }

            $value = '' === $style['unit'] ? $appearance[ $key ] : (int) $appearance[ $key ] . $style['unit'];
            $css  .= $style['variable'] . ':' . $value . ';';
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
                return preg_match('/' . self::COLOR_PATTERN . '/', $value) ? strtolower($value) : '';

            case 'font_family':
                return preg_match('/' . self::FONT_FAMILY_PATTERN . '/', $value) ? $value : '';

            case 'font_url':
                return self::sanitize_font_url($value);

            case 'radius':
                return preg_match('/' . self::RADIUS_PATTERN . '/', $value) && (int) $value <= self::RADIUS_MAX ? (int) $value : '';

            case 'button_text':
                $value = trim(strip_tags($value));

                return ('' !== $value && self::length($value) <= self::BUTTON_TEXT_MAX) ? $value : '';
        }

        return '';
    }

    private static function sanitize_font_url($value)
    {
        if (0 !== strpos($value, 'https://') || preg_match('/' . self::FONT_URL_FORBIDDEN_PATTERN . '/', $value)) {
            return '';
        }

        return false === filter_var($value, FILTER_VALIDATE_URL) ? '' : $value;
    }

    private static function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
