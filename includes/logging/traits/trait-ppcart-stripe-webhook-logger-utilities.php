<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Webhook_Logger_Utilities_Trait
{
    /**
     * Read a key from an array or object.
     *
     * @param object|array|null $object  Source value.
     * @param string            $key     Key/property name.
     * @param mixed             $default Default value.
     * @return mixed
     */
    private static function get($object, $key, $default = null)
    {
        if (is_array($object) && array_key_exists($key, $object)) {
            return $object[ $key ];
        }

        if (is_object($object) && isset($object->$key)) {
            return $object->$key;
        }

        return $default;
    }

    /**
     * Keep context scalar and safe.
     *
     * @param array $context Raw context.
     * @return array
     */
    private static function sanitize_context($context)
    {
        if (! is_array($context)) {
            return [];
        }

        $clean = [];
        foreach ($context as $key => $value) {
            $key = self::sanitize_key($key);
            if ('' === $key) {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $clean[ $key ] = $value;
            } elseif (is_scalar($value)) {
                $clean[ $key ] = self::sanitize_text((string) $value);
            }
        }

        return $clean;
    }

    /**
     * Sanitize plain text.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function sanitize_text($value)
    {
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field($value);
        }

        if (function_exists('wp_strip_all_tags')) {
            return is_scalar($value) ? trim(wp_strip_all_tags((string) $value)) : '';
        }

        return is_scalar($value) ? trim(strip_tags((string) $value)) : ''; // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter,WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Standalone fallback when WordPress is not loaded.
    }

    /**
     * Sanitize an array key.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function sanitize_key($value)
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }

        return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $value));
    }

    /**
     * Sanitize a filename.
     *
     * @param mixed $value Raw filename.
     * @return string
     */
    private static function sanitize_file_name($value)
    {
        if (function_exists('sanitize_file_name')) {
            return sanitize_file_name($value);
        }

        return preg_replace('/[^a-zA-Z0-9_.\-]/', '', basename((string) $value));
    }

    /**
     * JSON encode helper.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function json_encode($value)
    {
        if (function_exists('wp_json_encode')) {
            return wp_json_encode($value);
        }

        return json_encode($value); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for standalone tests.
    }
}
