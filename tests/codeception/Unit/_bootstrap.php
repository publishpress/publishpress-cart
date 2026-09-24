<?php

require_once dirname(__DIR__) . '/Support/WPError.php';
require_once dirname(__DIR__) . '/Support/WordPressStubContext.php';

use Tests\Support\WordPressStubContext;
use Tests\Support\WPError;

if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

if (! defined('PPCART_PLUGIN_ROOT')) {
    define('PPCART_PLUGIN_ROOT', dirname(__DIR__, 3) . '/');
}

define('PPCART_STRIPE_CONNECT_EXTRA_PERCENT', 2.0);

if (! defined('DB_NAME')) {
    define('DB_NAME', 'wordpress_unit_tests');
}

if (! function_exists('is_admin')) {
    function is_admin()
    {
        if (WordPressStubContext::has('is_admin')) {
            return WordPressStubContext::invoke('is_admin', func_get_args());
        }

        return false;
    }
}

if (! function_exists('absint')) {
    function absint($value)
    {
        return abs((int) $value);
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('__')) {
    function __($text, $domain = 'default')
    {
        if (WordPressStubContext::has('__')) {
            return WordPressStubContext::invoke('__', func_get_args());
        }

        return $text;
    }
}

if (! function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('_n')) {
    function _n($single, $plural, $number)
    {
        return 1 === absint($number) ? $single : $plural;
    }
}

if (! function_exists('trailingslashit')) {
    function trailingslashit($path)
    {
        return rtrim((string) $path, '/\\') . '/';
    }
}

if (! function_exists('wp_json_encode')) {
    function wp_json_encode($value, $flags = 0)
    {
        return json_encode($value, $flags);
    }
}

if (! function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4()
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        );
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $key));
    }
}

if (! function_exists('sanitize_email')) {
    function sanitize_email($value)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return false === filter_var($value, FILTER_VALIDATE_EMAIL) ? '' : $value;
    }
}

if (! function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value)
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('wp_kses_post')) {
    function wp_kses_post($value)
    {
        return is_scalar($value) ? strip_tags((string) $value, '<p><a><strong><em>') : '';
    }
}

if (! function_exists('esc_url_raw')) {
    function esc_url_raw($value)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        // Mirrors the part of WordPress's behaviour these tests depend on: query
        // separators survive, unlike esc_url(), which entity-encodes them.
        return preg_replace('|[^a-z0-9\-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $value);
    }
}

if (! function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $_context = 'display')
    {
        $url = trim((string) $url);
        if ('' === $url) {
            return $url;
        }

        // Enough of WordPress esc_url() for unit attribute assertions: neutralize
        // quote/angle-bracket breakouts without inventing a scheme.
        return str_replace(
            ['"', "'", '<', '>'],
            ['%22', '%27', '', ''],
            $url
        );
    }
}

if (! function_exists('get_the_ID')) {
    function get_the_ID()
    {
        return WordPressStubContext::invoke('get_the_ID', func_get_args());
    }
}

WordPressStubContext::set(
    'get_the_ID',
    static function () {
        return 0;
    }
);

if (! function_exists('shortcode_atts')) {
    function shortcode_atts($pairs, $atts, $shortcode = '')
    {
        $atts = (array) $atts;
        $out  = [];
        foreach ($pairs as $name => $default) {
            $out[ $name ] = array_key_exists($name, $atts) ? $atts[ $name ] : $default;
        }

        return $out;
    }
}


if (! function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($path)
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0755, true);
    }
}

if (! function_exists('maybe_unserialize')) {
    function maybe_unserialize($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        $unserialized = @unserialize($value);

        return (false === $unserialized && 'b:0;' !== $value) ? $value : $unserialized;
    }
}

if (! function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return $value instanceof WPError || $value instanceof WP_Error;
    }
}

if (! function_exists('get_post_status_object')) {
    function get_post_status_object($status)
    {
        return WordPressStubContext::invoke('get_post_status_object', func_get_args());
    }
}

if (! function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false)
    {
        return WordPressStubContext::invoke('get_post_meta', func_get_args());
    }
}

if (! function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value)
    {
        return WordPressStubContext::invoke('update_post_meta', func_get_args());
    }
}

if (! function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        return WordPressStubContext::invoke('add_action', func_get_args());
    }
}

if (! function_exists('remove_action')) {
    function remove_action($hook_name, $callback, $priority = 10)
    {
        if (! WordPressStubContext::has('remove_action')) {
            return true;
        }

        return WordPressStubContext::invoke('remove_action', func_get_args());
    }
}

if (! function_exists('do_action')) {
    function do_action($hook_name, ...$args)
    {
        return WordPressStubContext::invoke('do_action', func_get_args());
    }
}

if (! function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1)
    {
        return WordPressStubContext::invoke('wp_verify_nonce', func_get_args());
    }
}

if (! function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        return WordPressStubContext::invoke('add_filter', func_get_args());
    }
}

if (! function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page)
    {
        return WordPressStubContext::invoke('add_settings_section', func_get_args());
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters($hook_name, $value)
    {
        return WordPressStubContext::invoke('apply_filters', func_get_args());
    }
}

WordPressStubContext::set(
    'add_action',
    static function () {
        return true;
    }
);

WordPressStubContext::set(
    'remove_action',
    static function () {
        return true;
    }
);

WordPressStubContext::set(
    'do_action',
    static function () {
        return null;
    }
);

WordPressStubContext::set(
    'wp_verify_nonce',
    static function () {
        return false;
    }
);

WordPressStubContext::set(
    'add_filter',
    static function () {
        return true;
    }
);

if (! function_exists('did_action')) {
    function did_action($hook_name)
    {
        return WordPressStubContext::invoke('did_action', func_get_args());
    }
}

if (! function_exists('wp_style_is')) {
    function wp_style_is($handle, $list = 'enqueued')
    {
        return WordPressStubContext::invoke('wp_style_is', func_get_args());
    }
}

if (! function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all')
    {
        return WordPressStubContext::invoke('wp_enqueue_style', func_get_args());
    }
}

if (! function_exists('wp_register_style')) {
    function wp_register_style($handle, $src, $deps = [], $ver = false, $media = 'all')
    {
        return WordPressStubContext::invoke('wp_register_style', func_get_args());
    }
}

if (! function_exists('wp_add_inline_style')) {
    function wp_add_inline_style($handle, $data)
    {
        return WordPressStubContext::invoke('wp_add_inline_style', func_get_args());
    }
}

if (! function_exists('wp_print_styles')) {
    function wp_print_styles($handles = false)
    {
        return WordPressStubContext::invoke('wp_print_styles', func_get_args());
    }
}

WordPressStubContext::set(
    'did_action',
    static function () {
        return 0;
    }
);

if (! function_exists('get_option')) {
    function get_option($option, $default = false)
    {
        return WordPressStubContext::invoke('get_option', func_get_args());
    }
}

if (! function_exists('get_site_option')) {
    function get_site_option($option, $default = false)
    {
        return WordPressStubContext::invoke('get_site_option', func_get_args());
    }
}

WordPressStubContext::set(
    'get_option',
    static function ($option, $default = false) {
        return $default;
    }
);

WordPressStubContext::set(
    'get_site_option',
    static function ($option, $default = false) {
        return $default;
    }
);

require_once PPCART_PLUGIN_ROOT . 'includes/functions/ajax-security.php';
require_once PPCART_PLUGIN_ROOT . 'includes/functions/request-sanitization.php';
require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-stripe-metadata.php';
require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-stripe-client.php';
require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-live.php';
require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-live-tables.php';
require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-meta.php';

if (! defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (! function_exists('get_transient')) {
    function get_transient($key)
    {
        return WordPressStubContext::invoke('get_transient', func_get_args());
    }
}

if (! function_exists('set_transient')) {
    function set_transient($key, $value, $expiration = 0)
    {
        return WordPressStubContext::invoke('set_transient', func_get_args());
    }
}

if (! function_exists('update_option')) {
    function update_option($key, $value)
    {
        return WordPressStubContext::invoke('update_option', func_get_args());
    }
}

if (! function_exists('delete_option')) {
    function delete_option($key)
    {
        return WordPressStubContext::invoke('delete_option', func_get_args());
    }
}

if (! function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook, $args = [])
    {
        if (! WordPressStubContext::has('wp_clear_scheduled_hook')) {
            return;
        }

        return WordPressStubContext::invoke('wp_clear_scheduled_hook', func_get_args());
    }
}

if (! function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = [])
    {
        if (! WordPressStubContext::has('wp_next_scheduled')) {
            return false;
        }

        return WordPressStubContext::invoke('wp_next_scheduled', func_get_args());
    }
}

if (! function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = [])
    {
        if (! WordPressStubContext::has('wp_schedule_event')) {
            return true;
        }

        return WordPressStubContext::invoke('wp_schedule_event', func_get_args());
    }
}

if (! function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return WordPressStubContext::invoke('get_current_user_id', func_get_args());
    }
}

if (! function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key = '', $single = false)
    {
        return WordPressStubContext::invoke('get_user_meta', func_get_args());
    }
}

if (! function_exists('update_user_meta')) {
    function update_user_meta($user_id, $meta_key, $meta_value, $prev_value = '')
    {
        return WordPressStubContext::invoke('update_user_meta', func_get_args());
    }
}

if (! function_exists('get_post')) {
    function get_post($post_id)
    {
        return WordPressStubContext::invoke('get_post', func_get_args());
    }
}

if (! function_exists('add_post_meta')) {
    function add_post_meta($post_id, $key, $value)
    {
        return WordPressStubContext::invoke('add_post_meta', func_get_args());
    }
}

if (! function_exists('wp_insert_post')) {
    function wp_insert_post($post_data, $wp_error = false)
    {
        return WordPressStubContext::invoke('wp_insert_post', func_get_args());
    }
}

if (! function_exists('get_object_taxonomies')) {
    function get_object_taxonomies($post_type)
    {
        return WordPressStubContext::invoke('get_object_taxonomies', func_get_args());
    }
}

if (! function_exists('wp_get_object_terms')) {
    function wp_get_object_terms($post_id, $taxonomy, $args = array())
    {
        return WordPressStubContext::invoke('wp_get_object_terms', func_get_args());
    }
}

if (! function_exists('wp_set_object_terms')) {
    function wp_set_object_terms($post_id, $terms, $taxonomy, $append = false)
    {
        return WordPressStubContext::invoke('wp_set_object_terms', func_get_args());
    }
}

if (! function_exists('add_shortcode')) {
    function add_shortcode()
    {
        return WordPressStubContext::invoke('add_shortcode', func_get_args());
    }
}

if (! function_exists('wp_rand')) {
    function wp_rand()
    {
        return WordPressStubContext::invoke('wp_rand', func_get_args());
    }
}

if (! function_exists('get_current_screen')) {
    function get_current_screen()
    {
        return WordPressStubContext::invoke('get_current_screen', func_get_args());
    }
}

if (! function_exists('get_admin_page_parent')) {
    function get_admin_page_parent()
    {
        return WordPressStubContext::invoke('get_admin_page_parent', func_get_args());
    }
}

if (! function_exists('get_post_type')) {
    function get_post_type()
    {
        return WordPressStubContext::invoke('get_post_type', func_get_args());
    }
}

if (! function_exists('wp_kses_allowed_html')) {
    function wp_kses_allowed_html($context = '')
    {
        return WordPressStubContext::invoke('wp_kses_allowed_html', func_get_args());
    }
}

if (! function_exists('wp_kses')) {
    function wp_kses($html, $allowed_html)
    {
        return WordPressStubContext::invoke('wp_kses', func_get_args());
    }
}

if (! function_exists('get_bloginfo')) {
    function get_bloginfo($show = '')
    {
        return WordPressStubContext::invoke('get_bloginfo', func_get_args());
    }
}

if (! function_exists('wp_upload_dir')) {
    function wp_upload_dir()
    {
        return WordPressStubContext::invoke('wp_upload_dir', func_get_args());
    }
}

if (! function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1)
    {
        return parse_url($url, $component);
    }
}

if (! function_exists('set_url_scheme')) {
    function set_url_scheme($url, $scheme = null)
    {
        if (null === $scheme) {
            return preg_replace('#^https?://#i', 'https://', $url);
        }

        return preg_replace('#^https?://#i', $scheme . '://', $url);
    }
}

if (! function_exists('untrailingslashit')) {
    function untrailingslashit($path)
    {
        return rtrim((string) $path, '/\\');
    }
}

if (! function_exists('path_join')) {
    function path_join($base, $path)
    {
        return rtrim((string) $base, '/\\') . '/' . ltrim((string) $path, '/\\');
    }
}

if (! function_exists('wp_normalize_path')) {
    function wp_normalize_path($path)
    {
        return str_replace('\\', '/', (string) $path);
    }
}

if (! function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }

        return stripslashes((string) $value);
    }
}

if (! function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return WordPressStubContext::invoke('current_user_can', func_get_args());
    }
}

if (! function_exists('wp_die')) {
    function wp_die($message = '')
    {
        WordPressStubContext::invoke('wp_die', func_get_args());
    }
}

if (! function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce')
    {
        return WordPressStubContext::invoke('check_admin_referer', func_get_args());
    }
}

if (! function_exists('get_edit_post_link')) {
    function get_edit_post_link($post = 0, $context = 'display')
    {
        return WordPressStubContext::invoke('get_edit_post_link', func_get_args());
    }
}
