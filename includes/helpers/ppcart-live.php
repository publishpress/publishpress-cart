<?php

/**
 * Canonical-only live CPT, cap, role, and custom-table helpers.
 *
 * Companion leftover-aware implementations load first via the early include.
 * These stubs do not redeclare. Without the companion, leftover stores look
 * missing.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ppcart_canonical_live_post_types')) {
    /**
     * @return array<string, string>
     */
    function ppcart_canonical_live_post_types()
    {
        return [
            'product'      => 'ppcart_product',
            'order'        => 'ppcart_order',
            'subscription' => 'ppcart_subscription',
        ];
    }
}

if (! function_exists('ppcart_canonical_live_pro_post_types')) {
    /**
     * @return array<string, string>
     */
    function ppcart_canonical_live_pro_post_types()
    {
        return [
            'collection'   => 'ppcart_collection',
            'us_path'      => 'ppcart_us_path',
            'membership'   => 'ppcart_membership',
            'upgrade_path' => 'ppcart_upgrade_path',
        ];
    }
}

if (! function_exists('ppcart_canonical_live_taxonomies')) {
    /**
     * @return array<string, string>
     */
    function ppcart_canonical_live_taxonomies()
    {
        return [
            'product_cat' => 'ppcart_product_cat',
            'product_tag' => 'ppcart_product_tag',
        ];
    }
}

if (! function_exists('ppcart_canonical_live_caps')) {
    /**
     * @return array<string, string>
     */
    function ppcart_canonical_live_caps()
    {
        return [
            'manager_option' => 'ppcart_manager_option',
            'manage_orders'  => 'ppcart_manage_orders',
        ];
    }
}

if (! function_exists('ppcart_canonical_live_roles')) {
    /**
     * @return array<string, string>
     */
    function ppcart_canonical_live_roles()
    {
        return [
            'cart_manager'       => 'ppcart_manager',
            'cart_administrator' => 'ppcart_administrator',
        ];
    }
}

if (! function_exists('ppcart_live_post_type')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_live_post_type($family)
    {
        $map = ppcart_canonical_live_post_types();

        return $map[ $family ] ?? '';
    }
}

if (! function_exists('ppcart_query_post_types')) {
    /**
     * @param string $family Family key.
     * @return array<int, string>
     */
    function ppcart_query_post_types($family)
    {
        $slug = ppcart_live_post_type($family);

        return $slug ? [ $slug ] : [];
    }
}

if (! function_exists('ppcart_live_taxonomy')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_live_taxonomy($family)
    {
        $map = ppcart_canonical_live_taxonomies();

        return $map[ $family ] ?? '';
    }
}

if (! function_exists('ppcart_query_taxonomies')) {
    /**
     * @param string $family Family key.
     * @return array<int, string>
     */
    function ppcart_query_taxonomies($family)
    {
        $slug = ppcart_live_taxonomy($family);

        return $slug ? [ $slug ] : [];
    }
}

if (! function_exists('ppcart_taxonomy_rewrite_slug')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_taxonomy_rewrite_slug($family)
    {
        return ppcart_live_taxonomy($family);
    }
}

if (! function_exists('ppcart_is_product_post_type')) {
    /**
     * @param string $slug Post type slug.
     * @return bool
     */
    function ppcart_is_product_post_type($slug)
    {
        return in_array((string) $slug, ppcart_query_post_types('product'), true);
    }
}

if (! function_exists('ppcart_is_order_post_type')) {
    /**
     * @param string $slug Post type slug.
     * @return bool
     */
    function ppcart_is_order_post_type($slug)
    {
        return in_array((string) $slug, ppcart_query_post_types('order'), true);
    }
}

if (! function_exists('ppcart_is_subscription_post_type')) {
    /**
     * @param string $slug Post type slug.
     * @return bool
     */
    function ppcart_is_subscription_post_type($slug)
    {
        return in_array((string) $slug, ppcart_query_post_types('subscription'), true);
    }
}

if (! function_exists('ppcart_live_cap')) {
    /**
     * @param string $family_or_key Family key or canonical capability.
     * @return string
     */
    function ppcart_live_cap($family_or_key)
    {
        $family_or_key = (string) $family_or_key;
        $map           = ppcart_canonical_live_caps();
        if (isset($map[ $family_or_key ])) {
            return $map[ $family_or_key ];
        }

        return $family_or_key;
    }
}

if (! function_exists('ppcart_user_can')) {
    /**
     * @param string $family_or_key Family key or canonical capability.
     * @return bool
     */
    function ppcart_user_can($family_or_key)
    {
        if (! function_exists('current_user_can')) {
            return false;
        }

        return current_user_can(ppcart_live_cap($family_or_key));
    }
}

if (! function_exists('ppcart_live_role')) {
    /**
     * @param string $family_or_role Family key or canonical role slug.
     * @return string
     */
    function ppcart_live_role($family_or_role)
    {
        $family_or_role = (string) $family_or_role;
        $map            = ppcart_canonical_live_roles();
        if (isset($map[ $family_or_role ])) {
            return $map[ $family_or_role ];
        }

        return $family_or_role;
    }
}

if (! function_exists('ppcart_canonical_post_type')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_canonical_post_type($family)
    {
        return ppcart_live_post_type($family);
    }
}

if (! function_exists('ppcart_canonical_taxonomy')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_canonical_taxonomy($family)
    {
        return ppcart_live_taxonomy($family);
    }
}

if (! function_exists('ppcart_known_post_types')) {
    /**
     * @param string $family Family key.
     * @return array<int, string>
     */
    function ppcart_known_post_types($family)
    {
        return ppcart_query_post_types($family);
    }
}

if (! function_exists('ppcart_known_pro_post_types')) {
    /**
     * @param string $family Family key.
     * @return array<int, string>
     */
    function ppcart_known_pro_post_types($family)
    {
        $slug = ppcart_live_pro_post_type($family);

        return $slug ? [ $slug ] : [];
    }
}

if (! function_exists('ppcart_query_pro_post_types')) {
    /**
     * @param string $family Family key.
     * @return array<int, string>
     */
    function ppcart_query_pro_post_types($family)
    {
        return ppcart_known_pro_post_types($family);
    }
}

if (! function_exists('ppcart_live_pro_post_type')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_live_pro_post_type($family)
    {
        $map = ppcart_canonical_live_pro_post_types();

        return $map[ $family ] ?? '';
    }
}

if (! function_exists('ppcart_is_pro_post_type')) {
    /**
     * @param string $family Family key.
     * @param string $slug   Post type slug.
     * @return bool
     */
    function ppcart_is_pro_post_type($family, $slug)
    {
        return in_array((string) $slug, ppcart_known_pro_post_types($family), true);
    }
}

if (! function_exists('ppcart_query_product_and_collection_post_types')) {
    /**
     * @return array<int, string>
     */
    function ppcart_query_product_and_collection_post_types()
    {
        return array_values(
            array_unique(
                array_merge(
                    ppcart_query_post_types('product'),
                    ppcart_query_pro_post_types('collection')
                )
            )
        );
    }
}

if (! function_exists('ppcart_all_known_post_types')) {
    /**
     * @return array<int, string>
     */
    function ppcart_all_known_post_types()
    {
        $out = [];
        foreach (array_keys(ppcart_canonical_live_post_types()) as $family) {
            $out = array_merge($out, ppcart_known_post_types($family));
        }
        foreach (array_keys(ppcart_canonical_live_pro_post_types()) as $family) {
            $out = array_merge($out, ppcart_known_pro_post_types($family));
        }

        return array_values(array_unique($out));
    }
}

if (! function_exists('ppcart_known_taxonomies')) {
    /**
     * @param string $family Family key.
     * @return array<int, string>
     */
    function ppcart_known_taxonomies($family)
    {
        return ppcart_query_taxonomies($family);
    }
}

if (! function_exists('ppcart_all_known_taxonomies')) {
    /**
     * @return array<int, string>
     */
    function ppcart_all_known_taxonomies()
    {
        $out = [];
        foreach (array_keys(ppcart_canonical_live_taxonomies()) as $family) {
            $out = array_merge($out, ppcart_known_taxonomies($family));
        }

        return array_values(array_unique($out));
    }
}

if (! function_exists('ppcart_product_singular_body_class')) {
    /**
     * @return string
     */
    function ppcart_product_singular_body_class()
    {
        $slug = ppcart_live_post_type('product');

        return $slug ? 'single-' . $slug : 'single-ppcart_product';
    }
}

if (! function_exists('ppcart_product_singular_body_selector')) {
    /**
     * @return string
     */
    function ppcart_product_singular_body_selector()
    {
        return '.' . ppcart_product_singular_body_class();
    }
}

if (! function_exists('ppcart_cpt_slug_migration_registration_post_types')) {
    /**
     * @return array<int, array{family: string, cpt_name: string, cap_type: string, show_ui: bool}>
     */
    function ppcart_cpt_slug_migration_registration_post_types()
    {
        $rows = [];
        foreach (ppcart_canonical_live_post_types() as $family => $slug) {
            $rows[] = [
                'family'   => $family,
                'cpt_name' => $slug,
                'cap_type' => $slug,
                'show_ui'  => true,
            ];
        }

        return $rows;
    }
}

if (! function_exists('ppcart_filtered_product_post_types')) {
    /**
     * @return array<int, string>
     */
    function ppcart_filtered_product_post_types()
    {
        $default    = ppcart_live_post_type('product');
        $post_types = (array) apply_filters('ppcart_product_post_type', $default);
        $post_types = array_merge($post_types, ppcart_query_post_types('product'));
        $post_types = array_filter(array_map('sanitize_key', $post_types));

        return $post_types ? array_values(array_unique($post_types)) : [ $default ];
    }
}

if (! function_exists('ppcart_sql_in_post_types')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_sql_in_post_types($family)
    {
        global $wpdb;

        $types = ppcart_query_post_types($family);
        if (! $types) {
            return "''";
        }

        $placeholders = implode(',', array_fill(0, count($types), '%s'));
        if (isset($wpdb) && is_object($wpdb) && method_exists($wpdb, 'prepare')) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholder string is generated from the count of sanitized canonical post types.
            return $wpdb->prepare($placeholders, $types);
        }

        return implode(',', array_map(static function ($type) {
            return "'" . str_replace("'", "''", (string) $type) . "'";
        }, $types));
    }
}

if (! function_exists('ppcart_sql_in_post_statuses')) {
    /**
     * @param string|array<int, string> $statuses Logical status slug or list.
     * @return string
     */
    function ppcart_sql_in_post_statuses($statuses)
    {
        global $wpdb;

        $slugs = [];
        foreach ((array) $statuses as $status) {
            if (class_exists('PPCart_Status_Labels')) {
                $slugs = array_merge($slugs, PPCart_Status_Labels::query_slugs($status));
            } else {
                $slugs[] = (string) $status;
            }
        }
        $slugs = array_values(array_unique($slugs));
        if (! $slugs) {
            return "''";
        }

        $placeholders = implode(',', array_fill(0, count($slugs), '%s'));
        if (isset($wpdb) && is_object($wpdb) && method_exists($wpdb, 'prepare')) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholder string is generated from the count of mapped status slugs.
            return $wpdb->prepare($placeholders, $slugs);
        }

        return implode(',', array_map(static function ($slug) {
            return "'" . str_replace("'", "''", (string) $slug) . "'";
        }, $slugs));
    }
}

if (! function_exists('ppcart_cpt_slug_migration_step1_mixed')) {
    /**
     * @return bool
     */
    function ppcart_cpt_slug_migration_step1_mixed()
    {
        return false;
    }
}

if (! function_exists('ppcart_cpt_slug_migration_maybe_seed_state')) {
    /**
     * @return void
     */
    function ppcart_cpt_slug_migration_maybe_seed_state()
    {
    }
}
