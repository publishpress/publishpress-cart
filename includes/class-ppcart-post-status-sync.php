<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Maps logical order/subscription statuses onto prefixed wp_posts.post_status slugs.
 */
class PPCart_Post_Status_Sync
{
    /**
     * @return void
     */
    public static function register()
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        add_filter('wp_insert_post_data', [ self::class, 'map_insert_post_data' ], 5, 2);
        add_action('pre_get_posts', [ self::class, 'expand_query_statuses' ], 8);
        add_filter('posts_where', [ self::class, 'apply_status_in_where' ], 10, 2);
    }

    /**
     * @param array<string, mixed> $data    Sanitized post data.
     * @param array<string, mixed> $postarr Raw post array.
     * @return array<string, mixed>
     */
    public static function map_insert_post_data($data, $postarr = [])
    {
        if (! is_array($data)) {
            return $data;
        }

        $type = isset($data['post_type']) ? (string) $data['post_type'] : '';
        if (! in_array($type, PPCart_Status_Labels::canonical_post_types(), true)) {
            return $data;
        }

        if (! isset($data['post_status']) || ! PPCart_Status_Labels::is_mapped($data['post_status'])) {
            return $data;
        }

        $data['post_status'] = PPCart_Status_Labels::registered_slug($data['post_status']);

        return $data;
    }

    /**
     * @param \WP_Query $query Query.
     * @return void
     */
    public static function expand_query_statuses($query)
    {
        if (! is_object($query) || ! method_exists($query, 'get') || ! method_exists($query, 'set')) {
            return;
        }

        $types = $query->get('post_type');
        if (empty($types) || $types === 'any') {
            return;
        }

        if (! array_intersect((array) $types, PPCart_Status_Labels::query_post_types())) {
            return;
        }

        $status = $query->get('post_status');
        if ($status === '' || $status === null || $status === 'any' || $status === 'all') {
            return;
        }

        $has_mapped = false;
        foreach ((array) $status as $item) {
            if (PPCart_Status_Labels::is_mapped($item)) {
                $has_mapped = true;
                break;
            }
        }
        if (! $has_mapped) {
            return;
        }

        $expanded = self::expand_query_status_value($status);
        $query->set('ppcart_post_status_in', (array) $expanded);
        // WP_Query drops unregistered slugs like leftover `paid`; apply them in posts_where.
        $query->set('post_status', 'any');
    }

    /**
     * @param string    $where SQL WHERE.
     * @param \WP_Query $query Query.
     * @return string
     */
    public static function apply_status_in_where($where, $query)
    {
        if (! is_object($query) || ! method_exists($query, 'get')) {
            return $where;
        }

        $in = $query->get('ppcart_post_status_in');
        if (! is_array($in) || $in === []) {
            return $where;
        }

        global $wpdb;
        if (! isset($wpdb) || ! is_object($wpdb) || ! method_exists($wpdb, 'prepare')) {
            return $where;
        }

        $placeholders = implode(',', array_fill(0, count($in), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholder string is generated from the number of status values.
        $prepared_statuses = $wpdb->prepare($placeholders, $in);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $prepared_statuses contains only values escaped by wpdb::prepare().
        $where .= " AND {$wpdb->posts}.post_status IN (" . $prepared_statuses . ') ';

        return $where;
    }

    /**
     * @param string|array<int, string> $status Query status value.
     * @return string|array<int, string>
     */
    public static function expand_query_status_value($status)
    {
        if ($status === 'any' || $status === 'all') {
            return $status;
        }

        $was_array = is_array($status);
        $list      = $was_array ? $status : [ $status ];
        $out       = [];

        foreach ($list as $item) {
            $item = (string) $item;
            if (PPCart_Status_Labels::is_mapped($item)) {
                foreach (PPCart_Status_Labels::query_slugs($item) as $slug) {
                    $out[] = $slug;
                }
            } else {
                $out[] = $item;
            }
        }

        $out = array_values(array_unique($out));

        if ($was_array || count($out) > 1) {
            return $out;
        }

        return isset($out[0]) ? $out[0] : $status;
    }

    /**
     * Rewrite leftover unprefixed post_status rows on canonical order/subscription CPTs.
     *
     * @return void
     */
    public static function migrate_stored_statuses()
    {
        global $wpdb;

        if (! isset($wpdb) || ! is_object($wpdb)) {
            return;
        }

        $types = PPCart_Status_Labels::canonical_post_types();
        if (! $types) {
            return;
        }

        $type_placeholders = implode(',', array_fill(0, count($types), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholder string is generated from the number of canonical post types.
        $prepared_types = $wpdb->prepare($type_placeholders, $types);

        foreach (PPCart_Status_Labels::registered_map() as $logical => $registered) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-shot upgrade rewrite of plugin-owned post_status slugs.
            $wpdb->query(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $prepared_types contains only values escaped by wpdb::prepare().
                    "UPDATE {$wpdb->posts} SET post_status = %s WHERE post_status = %s AND post_type IN (" . $prepared_types . ')',
                    $registered,
                    $logical
                )
            );
        }
    }
}
