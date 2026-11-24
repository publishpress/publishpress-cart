<?php

if (! defined('ABSPATH')) {
    exit;
}

function ppcart_block_style_normalize_settings($settings)
{
    $allowed_tokens = [
        'preset'       => [ 'minimal', 'carded', 'compact', 'contrast' ],
        'surfaceStyle' => [ 'card', 'boxed' ],
        'density'      => [ 'compact', 'spacious' ],
        'cornerRadius' => [ 'square', 'rounded' ],
    ];
    $normalized = [];

    foreach ($settings as $key => $value) {
        if (! is_scalar($value)) {
            continue;
        }
        if ('accentColor' === $key) {
            $value = ppcart_block_style_normalize_hex_color($value);

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

function ppcart_block_style_get_style_preset_defaults($preset)
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

function ppcart_block_style_get_density_values($density)
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

function ppcart_block_style_get_style_radius($radius)
{
    if ('square' === $radius) {
        return '0';
    }

    if ('rounded' === $radius) {
        return '10px';
    }

    return '4px';
}

function ppcart_block_style_get_field_height($density)
{
    if ('compact' === $density) {
        return '38px';
    }

    if ('spacious' === $density) {
        return '52px';
    }

    return '44px';
}

function ppcart_block_style_get_button_padding($density)
{
    if ('spacious' === $density) {
        return '15px 20px';
    }

    if ('compact' === $density) {
        return '10px 14px';
    }

    return '12px 18px';
}

function ppcart_block_style_normalize_hex_color($color)
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

function ppcart_block_style_mix_hex_color($color, $target, $weight, $fallback)
{
    $color  = ppcart_block_style_normalize_hex_color($color);
    $target = ppcart_block_style_normalize_hex_color($target);

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

function ppcart_block_style_tokens(array $settings): array
{
    $raw = ppcart_block_style_normalize_settings($settings);
    if (! $raw) {
        return [];
    }
    $settings = array_merge(ppcart_block_style_get_style_preset_defaults($raw['preset'] ?? ''), $raw);
    $tokens = [];
    if (! empty($raw['preset']) || ! empty($raw['accentColor'])) {
        $accent = $settings['accentColor'] ?? '#2271b1';
        $tokens['--ppcart-accent'] = $accent;
        $tokens['--ppcart-accent-hover'] = ppcart_block_style_mix_hex_color($accent, '#000000', 0.12, '#135e96');
        $tokens['--ppcart-accent-soft'] = ppcart_block_style_mix_hex_color($accent, '#ffffff', 0.88, '#eef6fc');
    }
    $density = $settings['density'] ?? '';
    if (! empty($raw['preset']) || ! empty($raw['density']) || ! empty($raw['cornerRadius'])) {
        $tokens['--ppcart-radius'] = ppcart_block_style_get_style_radius($settings['cornerRadius'] ?? '');
        foreach (ppcart_block_style_get_density_values($density) as $key => $value) {
            $tokens['--ppcart-' . str_replace('_', '-', $key)] = $value;
        }
        $tokens['--ppcart-field-height'] = ppcart_block_style_get_field_height($density);
        $tokens['--ppcart-button-padding'] = ppcart_block_style_get_button_padding($density);
    }
    if (! empty($settings['surfaceStyle'])) {
        $card = 'card' === $settings['surfaceStyle'];
        $tokens['--ppcart-surface'] = $card ? '#ffffff' : '#f8fafc';
        $tokens['--ppcart-surface-border'] = $card ? '#e5e7eb' : '#dcdcde';
        $tokens['--ppcart-surface-shadow'] = $card ? '0 16px 40px rgba(17, 24, 39, 0.08)' : 'none';
    }
    return $tokens;
}
