<?php

/**
 * Canonical-only post/user meta helpers.
 *
 * Companion leftover-aware implementations (including `ppcart_legacy_meta_key`
 * and leftover read-through) load first from publishpress-cart-compat.
 * These stubs do not redeclare. Free never reads or rewrites leftover `_sc_*`
 * rows; that belongs only in cart-compat.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ppcart_meta_key')) {
    /**
     * @param string $name Suffix or canonical meta key.
     * @return string
     */
    function ppcart_meta_key($name)
    {
        return '_ppcart_' . ppcart_meta_key_suffix($name);
    }
}

if (! function_exists('ppcart_meta_key_suffix')) {
    /**
     * Strip canonical Cart prefixes only. Leftover `_sc_*` / `sc_*` stripping
     * lives in publishpress-cart-compat.
     *
     * @param string $name Meta key or suffix.
     * @return string
     */
    function ppcart_meta_key_suffix($name)
    {
        $name = (string) $name;

        if (0 === strpos($name, '_ppcart_')) {
            return substr($name, 8);
        }

        if (0 === strpos($name, 'ppcart_')) {
            return substr($name, 7);
        }

        return $name;
    }
}

if (! function_exists('ppcart_query_meta_keys')) {
    /**
     * @param string $name Suffix or canonical meta key.
     * @return array<int, string>
     */
    function ppcart_query_meta_keys($name)
    {
        return [ ppcart_meta_key($name) ];
    }
}

if (! function_exists('ppcart_is_meta_field_id')) {
    /**
     * Match a field id to the canonical key or bare suffix only.
     *
     * @param string $id   Field id / meta key.
     * @param string $name Suffix or canonical name.
     * @return bool
     */
    function ppcart_is_meta_field_id($id, $name)
    {
        $id = (string) $id;

        return $id === ppcart_meta_key($name) || $id === ppcart_meta_key_suffix($name);
    }
}

if (! function_exists('ppcart_sql_in_meta_keys')) {
    /**
     * @param string $name Suffix or leftover/canonical name.
     * @return string
     */
    function ppcart_sql_in_meta_keys($name)
    {
        global $wpdb;

        $keys = ppcart_query_meta_keys($name);
        if (! $keys) {
            return "''";
        }

        $placeholders = implode(',', array_fill(0, count($keys), '%s'));
        if (isset($wpdb) && is_object($wpdb) && method_exists($wpdb, 'prepare')) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholder string is generated from the count of canonical meta keys.
            return $wpdb->prepare($placeholders, $keys);
        }

        return implode(',', array_map(static function ($key) {
            return "'" . str_replace("'", "''", (string) $key) . "'";
        }, $keys));
    }
}

if (! function_exists('ppcart_meta_query_for')) {
    /**
     * @param string $name    Suffix or leftover/canonical name.
     * @param mixed  $value   Compared value, or null to match key presence only.
     * @param string $compare Compare operator.
     * @param string $type    Value type.
     * @return array<string, mixed>
     */
    function ppcart_meta_query_for($name, $value = null, $compare = '=', $type = '')
    {
        $clause = [ 'key' => ppcart_meta_key($name) ];
        if (null !== $value) {
            $clause['value']   = $value;
            $clause['compare'] = $compare;
            if ('' !== $type) {
                $clause['type'] = $type;
            }
        }

        return $clause;
    }
}

if (! function_exists('ppcart_get_post_meta')) {
    /**
     * @param int    $post_id Post ID.
     * @param string $name    Suffix or leftover/canonical name.
     * @param bool   $single  Single value.
     * @return mixed
     */
    function ppcart_get_post_meta($post_id, $name, $single = true)
    {
        return get_post_meta($post_id, ppcart_meta_key($name), $single);
    }
}

if (! function_exists('ppcart_update_post_meta')) {
    /**
     * @param int    $post_id    Post ID.
     * @param string $name       Suffix or leftover/canonical name.
     * @param mixed  $meta_value Value.
     * @param mixed  $prev_value Previous value.
     * @return int|bool
     */
    function ppcart_update_post_meta($post_id, $name, $meta_value, $prev_value = '')
    {
        return update_post_meta($post_id, ppcart_meta_key($name), $meta_value, $prev_value);
    }
}

if (! function_exists('ppcart_delete_post_meta')) {
    /**
     * @param int    $post_id    Post ID.
     * @param string $name       Suffix or leftover/canonical name.
     * @param mixed  $meta_value Unique value to delete, or empty for all.
     * @return bool
     */
    function ppcart_delete_post_meta($post_id, $name, $meta_value = '')
    {
        return delete_post_meta($post_id, ppcart_meta_key($name), $meta_value);
    }
}

if (! function_exists('ppcart_add_post_meta')) {
    /**
     * @param int    $post_id    Post ID.
     * @param string $name       Suffix or leftover/canonical name.
     * @param mixed  $meta_value Value.
     * @param bool   $unique     Unique.
     * @return int|false
     */
    function ppcart_add_post_meta($post_id, $name, $meta_value, $unique = false)
    {
        return add_post_meta($post_id, ppcart_meta_key($name), $meta_value, $unique);
    }
}

if (! function_exists('ppcart_add_user_meta')) {
    /**
     * @param int    $user_id    User ID.
     * @param string $name       Suffix or leftover/canonical name.
     * @param mixed  $meta_value Value.
     * @param bool   $unique     Unique.
     * @return int|false
     */
    function ppcart_add_user_meta($user_id, $name, $meta_value, $unique = false)
    {
        return add_user_meta($user_id, ppcart_meta_key($name), $meta_value, $unique);
    }
}

if (! function_exists('ppcart_get_user_meta')) {
    /**
     * @param int    $user_id User ID.
     * @param string $name    Suffix or leftover/canonical name.
     * @param bool   $single  Single value.
     * @return mixed
     */
    function ppcart_get_user_meta($user_id, $name, $single = true)
    {
        return get_user_meta($user_id, ppcart_meta_key($name), $single);
    }
}

if (! function_exists('ppcart_update_user_meta')) {
    /**
     * @param int    $user_id    User ID.
     * @param string $name       Suffix or leftover/canonical name.
     * @param mixed  $meta_value Value.
     * @param mixed  $prev_value Previous value.
     * @return int|bool
     */
    function ppcart_update_user_meta($user_id, $name, $meta_value, $prev_value = '')
    {
        return update_user_meta($user_id, ppcart_meta_key($name), $meta_value, $prev_value);
    }
}

if (! function_exists('ppcart_delete_user_meta')) {
    /**
     * @param int    $user_id    User ID.
     * @param string $name       Suffix or leftover/canonical name.
     * @param mixed  $meta_value Unique value to delete, or empty for all.
     * @return bool
     */
    function ppcart_delete_user_meta($user_id, $name, $meta_value = '')
    {
        return delete_user_meta($user_id, ppcart_meta_key($name), $meta_value);
    }
}

if (! function_exists('ppcart_custom_meta_values')) {
    /**
     * @param array<string, mixed> $custom Custom meta array.
     * @param string               $name   Suffix or leftover/canonical name.
     * @return array<int, mixed>|null
     */
    function ppcart_custom_meta_values(array $custom, $name)
    {
        $canonical = ppcart_meta_key($name);
        if (isset($custom[ $canonical ]) && is_array($custom[ $canonical ]) && $custom[ $canonical ] !== []) {
            return $custom[ $canonical ];
        }

        return null;
    }
}

if (! function_exists('ppcart_shift_custom_meta')) {
    /**
     * @param array<string, mixed> $custom Custom meta array.
     * @param string               $name   Suffix or leftover/canonical name.
     * @return mixed|null
     */
    function ppcart_shift_custom_meta(array $custom, $name)
    {
        $values = ppcart_custom_meta_values($custom, $name);
        if (! is_array($values) || [] === $values) {
            return null;
        }

        return array_shift($values);
    }
}
