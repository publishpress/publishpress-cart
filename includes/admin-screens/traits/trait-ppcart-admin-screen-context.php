<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Screen_Context_Trait
{
    /**
         * Builds the current request context.
         *
         * @param string $hook_suffix Current admin hook suffix.
         * @return array
         */
    private static function get_context($hook_suffix = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/screen-context-get-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Reads and normalizes a query-string key.
         *
         * @param string $key Query key.
         * @return string
         */
    private static function query_key($key)
    {
        $raw_value = self::request_value(INPUT_GET, $key);
        if (null === $raw_value || false === $raw_value) {
            return '';
        }

        $value = self::sanitize_request_value($raw_value);

        return self::normalize_key($value);
    }

    /**
         * Resolves the post type currently being edited when the query carries no
         * explicit post_type (e.g. the post.php edit screen during admin_init,
         * before the current screen object is available).
         *
         * @return string
         */
    private static function edited_post_type()
    {
        if (! function_exists('get_post_type')) {
            return '';
        }

        $post_id = self::request_value(INPUT_GET, 'post');
        if (null === $post_id || false === $post_id) {
            $post_id = self::request_value(INPUT_POST, 'post_ID');
        }

        $post_id = absint($post_id);
        if (! $post_id) {
            return '';
        }

        $post_type = get_post_type($post_id);

        return is_string($post_type) && '' !== $post_type ? self::normalize_key($post_type) : '';
    }

    /**
         * Finds standalone route flags in the current request.
         *
         * @return array
         */
    private static function route_params()
    {
        $params = [];

        foreach (self::STANDALONE_ROUTE_PARAMS as $param) {
            $raw_value = self::request_value(INPUT_POST, $param);
            if (null === $raw_value || false === $raw_value) {
                $raw_value = self::request_value(INPUT_GET, $param);
            }

            if (null === $raw_value || false === $raw_value) {
                continue;
            }

            $value = self::sanitize_request_value($raw_value);

            if ('' !== (string) $value) {
                $params[] = $param;
            }
        }

        return $params;
    }

    /**
         * Reads a scalar request value.
         *
         * @param int    $input_type Input type.
         * @param string $key        Request key.
         * @return mixed|null
         */
    private static function request_value($input_type, $key)
    {
        $raw_value = filter_input($input_type, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_SCALAR);
        if (null !== $raw_value && false !== $raw_value) {
            return $raw_value;
        }

        if (INPUT_GET === $input_type) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only screen detection fallback for CLI tests; sanitized before use.
            return $_GET[ $key ] ?? null;
        }

        if (INPUT_POST === $input_type) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only route detection fallback for CLI tests; sanitized before use.
            return $_POST[ $key ] ?? null;
        }

        return null;
    }

    /**
         * Sanitizes a scalar request value without requiring WordPress in isolated tests.
         *
         * @param mixed $value Request value.
         * @return string
         */
    private static function sanitize_request_value($value)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = self::unslash($value);
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field((string) $value);
        }

        if (function_exists('wp_strip_all_tags')) {
            return wp_strip_all_tags((string) $value);
        }

        $value = preg_replace('/<[^>]*>/', '', (string) $value);
        $value = preg_replace('/[\r\n\t]+/', ' ', $value);

        return trim($value);
    }

    /**
         * Unslashes request values without requiring WordPress in isolated tests.
         *
         * @param mixed $value Value.
         * @return mixed
         */
    private static function unslash($value)
    {
        if (function_exists('wp_unslash')) {
            return wp_unslash($value);
        }

        if (is_array($value)) {
            return array_map([ __CLASS__, 'unslash' ], $value);
        }

        return is_string($value) ? stripslashes($value) : $value;
    }
}
