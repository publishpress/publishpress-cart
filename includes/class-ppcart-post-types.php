<?php

defined('ABSPATH') || exit;

/**
 * Register custom post type
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */

class PPCart_Post_Types
{
    /**
         * Creates a new custom post type
         *
         * @since 1.0.0
         * @access public
         * @uses    register_post_type()
         */

    /**
     * Create post types
     */

    public static function register_single_post_type($cap_type, $plural, $single, $cpt_name, $supports = false, $public = false, $show_ui = true)
    {
        include __DIR__ . '/templates/post-types-register-single-post-type.php';
    }

    /**
     * Keep previously published routes usable alongside the canonical base.
     */
    private static function register_legacy_routes($post_type, $canonical_base)
    {
        $option = '_ppcart_legacy_routes_' . $post_type;
        $legacy_routes = get_option($option);
        if (false === $legacy_routes) {
            $legacy_routes = [];
            foreach ((array) get_option('rewrite_rules', []) as $pattern => $query) {
                if (! is_string($query)) {
                    continue;
                }

                $parameters = [];
                wp_parse_str((string) wp_parse_url($query, PHP_URL_QUERY), $parameters);
                if (! isset($parameters[$post_type]) && ($parameters['post_type'] ?? '') !== $post_type) {
                    continue;
                }

                global $wp_rewrite;
                $prefix = $wp_rewrite->root . $canonical_base . '/';
                if (0 !== strpos($pattern, $prefix)) {
                    $legacy_routes[$pattern] = $query;
                }
            }

            if (add_option($option, $legacy_routes) && $legacy_routes) {
                // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Regenerate canonical and legacy routes once after registration.
                flush_rewrite_rules(false);
            }
        }

        foreach ($legacy_routes as $pattern => $query) {
            add_rewrite_rule($pattern, $query, 'top');
        }
    }


    private function register_single_post_type_taxonomy($plural, $single, $tax_name)
    {
        include __DIR__ . '/templates/post-types-register-single-taxonomy.php';
    }


    public function create_custom_post_type()
    {
        include __DIR__ . '/templates/post-types-create-custom-post-type.php';
    }
}
