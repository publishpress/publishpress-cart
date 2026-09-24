<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Screen_Matching_Trait
{
    /**
         * Checks a submenu page against the Cart admin hook suffix.
         *
         * @param string $page_slug    Submenu page slug.
         * @param string $current_hook Current admin hook suffix.
         * @return bool
         */
    private static function is_submenu_page_screen($page_slug, $current_hook = '')
    {
        $page_slug = self::normalize_key($page_slug);

        return self::is_page_screen($page_slug, self::MENU_HOOK_PREFIX . $page_slug, $current_hook);
    }

    /**
         * Checks an exact page and hook pair.
         *
         * @param string $page_slug   Page slug.
         * @param string $hook_suffix Expected hook suffix.
         * @param string $current_hook Current admin hook suffix.
         * @return bool
         */
    private static function is_page_screen($page_slug, $hook_suffix, $current_hook = '')
    {
        $context     = self::get_context($current_hook);
        $page_slug   = self::normalize_key($page_slug);
        $hook_suffix = self::normalize_key($hook_suffix);

        if ($page_slug === $context['page'] || $hook_suffix === $context['hook_suffix']) {
            return true;
        }

        if (! $context['has_screen']) {
            return false;
        }

        return $hook_suffix === $context['screen_id']
            || $hook_suffix === $context['screen_base']
            || $page_slug === $context['screen_id']
            || $page_slug === $context['screen_base'];
    }

    /**
         * Checks whether the request targets a core WordPress admin screen.
         *
         * @param array $context Request context.
         * @return bool
         */
    private static function is_excluded_core_admin_screen($context)
    {
        if (! $context['has_screen']) {
            return false;
        }

        $excluded_bases = [
            'dashboard',
            'dashboard-network',
            'dashboard-user',
        ];

        return in_array($context['screen_base'], $excluded_bases, true)
            || in_array($context['screen_id'], $excluded_bases, true);
    }

    /**
         * Checks whether the resolved admin parent belongs to PublishPress Cart.
         *
         * @param string $admin_page_parent Normalized admin page parent.
         * @return bool
         */
    private static function is_cart_admin_menu_parent($admin_page_parent)
    {
        if ('' === $admin_page_parent) {
            return false;
        }

        return self::PAGE_DASHBOARD === $admin_page_parent;
    }

    /**
     * Checks whether a page slug is known or uses a canonical Cart prefix.
         *
         * @param string $page Page slug.
         * @return bool
         */
    private static function is_known_or_compatible_page_slug($page)
    {
        if (in_array($page, self::PAGE_SLUGS, true)) {
            return true;
        }

        foreach (self::COMPATIBLE_PAGE_PREFIXES as $prefix) {
            if (0 === strpos($page, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether a hook suffix is known or uses a canonical Cart prefix.
         *
         * @param string $hook_suffix Hook suffix.
         * @return bool
         */
    private static function is_known_hook_suffix($hook_suffix)
    {
        if (in_array($hook_suffix, self::HOOK_SUFFIXES, true)) {
            return true;
        }

        foreach (self::COMPATIBLE_HOOK_PREFIXES as $prefix) {
            if (0 === strpos($hook_suffix, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether a screen id/base is known or uses a canonical Cart prefix.
         *
         * @param string $screen_id Screen id or base.
         * @return bool
         */
    private static function is_known_or_compatible_screen_id($screen_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/screen-matching-is-known-or-compatible-screen-id.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Checks a post type against the authoritative list.
         *
         * @param string $post_type Post type.
         * @return bool
         */
    private static function is_known_post_type($post_type)
    {
        return in_array($post_type, self::all_post_types(), true);
    }

    /**
         * Checks a taxonomy against the authoritative list.
         *
         * @param string $taxonomy Taxonomy.
         * @return bool
         */
    private static function is_known_taxonomy($taxonomy)
    {
        return in_array($taxonomy, self::all_taxonomies(), true);
    }

    /**
         * Normalizes a list of identifiers.
         *
         * @param array $items Identifiers.
         * @return array
         */
    private static function normalize_list($items)
    {
        return array_values(array_filter(array_map([ __CLASS__, 'normalize_key' ], $items)));
    }

    /**
         * Normalizes a WordPress key without requiring WordPress in isolated tests.
         *
         * @param mixed $value Value.
         * @return string
         */
    private static function normalize_key($value)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = (string) $value;
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }

        $value = strtolower($value);
        return preg_replace('/[^a-z0-9_\\-]/', '', $value);
    }
}
