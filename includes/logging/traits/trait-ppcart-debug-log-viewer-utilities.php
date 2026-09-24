<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Debug_Log_Viewer_Utilities_Trait
{
    /**
     * Format admin time from a row.
     *
     * @param array $row Parsed row.
     * @return string
     */
    private static function format_admin_time($row)
    {
        $timestamp = absint(self::get($row, 'timestamp', 0));
        if (! $timestamp) {
            return (string) self::get($row, 'time_display', '');
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Format a local ID with hash prefix.
     *
     * @param mixed $id ID.
     * @return string
     */
    private static function format_hash_id($id)
    {
        return $id ? '#' . absint($id) : '-';
    }

    /**
     * Get the value from an array or object.
     *
     * @param mixed  $source  Source.
     * @param string $key     Key.
     * @param mixed  $default Default.
     * @return mixed
     */
    private static function get($source, $key, $default = '')
    {
        if (is_array($source) && array_key_exists($key, $source)) {
            return $source[ $key ];
        }

        if (is_object($source) && isset($source->$key)) {
            return $source->$key;
        }

        return $default;
    }

    /**
     * JSON encode with readable fallback.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function json_encode($value)
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
        if (function_exists('wp_json_encode')) {
            return (string) wp_json_encode($value, $flags);
        }

        return (string) json_encode($value, $flags); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for standalone tests.
    }

    /**
     * Normalize debug level.
     *
     * @param string $level Raw level.
     * @return string
     */
    private static function normalize_level($level)
    {
        $level = strtoupper(preg_replace('/[^A-Za-z_]/', '', (string) $level));
        return in_array($level, self::allowed_levels(), true) ? $level : 'UNKNOWN';
    }

    /**
     * Get valid debug levels.
     *
     * @return array
     */
    private static function allowed_levels()
    {
        return [ 'SUCCESS', 'STATUS', 'NOTICE', 'WARNING', 'FAILURE', 'CRITICAL', 'UNKNOWN' ];
    }

    /**
     * Detect legacy section-break lines.
     *
     * @param string $line Raw line.
     * @return bool
     */
    private static function is_section_break($line)
    {
        return (bool) preg_match('/^-{5,}$/', trim($line));
    }
}
