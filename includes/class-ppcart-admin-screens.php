<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/admin-screens/traits/trait-ppcart-admin-screen-matching.php';

require_once __DIR__ . '/admin-screens/traits/trait-ppcart-admin-screen-context.php';

/**
 * Centralized PublishPress Cart admin screen detection.
 */
class PPCart_Admin_Screens
{
    use PPCart_Admin_Screen_Matching_Trait;
    use PPCart_Admin_Screen_Context_Trait;

    public const PAGE_DASHBOARD        = 'ppcart';
    public const PAGE_SETTINGS         = 'ppcart-settings';
    public const PAGE_REPORTS          = 'ppcart-reports';
    public const PAGE_CUSTOMER_REPORTS = 'ppcart-customer-reports';
    public const PAGE_CONTACTS         = 'ppcart-contacts';
    public const PAGE_EXTENSIONS       = 'ppcart-extensions';
    public const PAGE_WHITE_LABEL      = 'ppcart-white-label';
    public const PAGE_AFFILIATES       = 'ppcart-affiliates';

    public const ADMIN_BODY_CLASS = 'ppcart-admin-page';
    public const MENU_HOOK_PREFIX = 'ppcart_page_';

    public const HOOK_DASHBOARD        = 'toplevel_page_ppcart';
    public const HOOK_SETTINGS         = 'ppcart_page_ppcart-settings';
    public const HOOK_REPORTS          = 'ppcart_page_ppcart-reports';
    public const HOOK_CUSTOMER_REPORTS = 'ppcart_page_ppcart-customer-reports';
    public const HOOK_CONTACTS         = 'ppcart_page_ppcart-contacts';
    public const HOOK_EXTENSIONS       = 'ppcart_page_ppcart-extensions';
    public const HOOK_WHITE_LABEL      = 'ppcart_page_ppcart-white-label';
    public const HOOK_AFFILIATES       = 'ppcart_page_ppcart-affiliates';

    public const PAGE_SLUGS = [
        self::PAGE_DASHBOARD,
        self::PAGE_SETTINGS,
        self::PAGE_REPORTS,
        self::PAGE_CUSTOMER_REPORTS,
        self::PAGE_CONTACTS,
        self::PAGE_EXTENSIONS,
        self::PAGE_WHITE_LABEL,
        self::PAGE_AFFILIATES,
    ];

    public const HOOK_SUFFIXES = [
        self::HOOK_DASHBOARD,
        self::HOOK_SETTINGS,
        self::HOOK_REPORTS,
        self::HOOK_CUSTOMER_REPORTS,
        self::HOOK_CONTACTS,
        self::HOOK_EXTENSIONS,
        self::HOOK_WHITE_LABEL,
        self::HOOK_AFFILIATES,
    ];

    public const POST_TYPE_PRODUCT      = 'ppcart_product';
    public const POST_TYPE_ORDER        = 'ppcart_order';
    public const POST_TYPE_SUBSCRIPTION = 'ppcart_subscription';
    public const POST_TYPE_PRODUCT_CANONICAL      = 'ppcart_product';
    public const POST_TYPE_ORDER_CANONICAL        = 'ppcart_order';
    public const POST_TYPE_SUBSCRIPTION_CANONICAL = 'ppcart_subscription';
    public const POST_TYPE_UPSELL_PATH  = 'ppcart_us_path';
    public const POST_TYPE_MEMBERSHIP   = 'ppcart_membership';
    public const POST_TYPE_COLLECTION   = 'ppcart_collection';
    public const POST_TYPE_UPGRADE_PATH = 'ppcart_upgrade_path';

    public const TAXONOMY_PRODUCT_CATEGORY = 'ppcart_product_cat';
    public const TAXONOMY_PRODUCT_TAG      = 'ppcart_product_tag';
    public const TAXONOMY_PRODUCT_CATEGORY_CANONICAL = 'ppcart_product_cat';
    public const TAXONOMY_PRODUCT_TAG_CANONICAL      = 'ppcart_product_tag';

    public const ROUTE_DEBUG_LOG_VIEW      = 'ppcart_view_log';
    public const ROUTE_DEBUG_LOG_DOWNLOAD  = 'ppcart_download_log';
    public const ROUTE_DEBUG_LOG_RESET     = 'ppcart_reset_log';
    public const ROUTE_STRIPE_LOG_VIEW     = 'ppcart_view_stripe_webhook_log';
    public const ROUTE_STRIPE_LOG_DOWNLOAD = 'ppcart_download_stripe_webhook_log';
    public const ROUTE_STRIPE_LOG_CLEAR    = 'ppcart_clear_stripe_webhook_log';

    public const POST_TYPES = [
        self::POST_TYPE_PRODUCT,
        self::POST_TYPE_ORDER,
        self::POST_TYPE_SUBSCRIPTION,
        self::POST_TYPE_UPSELL_PATH,
        self::POST_TYPE_MEMBERSHIP,
        self::POST_TYPE_COLLECTION,
        self::POST_TYPE_UPGRADE_PATH,
    ];

    public const TAXONOMIES = [
        self::TAXONOMY_PRODUCT_CATEGORY,
        self::TAXONOMY_PRODUCT_TAG,
    ];

    public const STANDALONE_ROUTE_PARAMS = [
        self::ROUTE_DEBUG_LOG_VIEW,
        self::ROUTE_DEBUG_LOG_DOWNLOAD,
        self::ROUTE_DEBUG_LOG_RESET,
        self::ROUTE_STRIPE_LOG_VIEW,
        self::ROUTE_STRIPE_LOG_DOWNLOAD,
        self::ROUTE_STRIPE_LOG_CLEAR,
    ];

    public const COMPATIBLE_PAGE_PREFIXES = [
        'ppcart-',
    ];

    public const COMPATIBLE_HOOK_PREFIXES = [
        self::MENU_HOOK_PREFIX,
    ];

    /**
     * Returns the top-level Cart admin menu slug.
     *
     * @return string
     */
    public static function menu_slug()
    {
        return self::PAGE_DASHBOARD;
    }

    /**
     * Canonical and leftover CPT slugs this plugin recognizes.
     *
     * @return array<int, string>
     */
    public static function all_post_types()
    {
        if (function_exists('ppcart_all_known_post_types')) {
            $types = ppcart_all_known_post_types();
            if ($types) {
                return $types;
            }
        }

        return self::POST_TYPES;
    }

    /**
     * Canonical and leftover taxonomy slugs this plugin recognizes.
     *
     * @return array<int, string>
     */
    public static function all_taxonomies()
    {
        if (function_exists('ppcart_all_known_taxonomies')) {
            $types = ppcart_all_known_taxonomies();
            if ($types) {
                return $types;
            }
        }

        return self::TAXONOMIES;
    }

    /**
     * Post types that use the modern Cart admin shell (excludes membership).
     *
     * @return array<int, string>
     */
    public static function modern_admin_post_types()
    {
        $types = [];
        if (function_exists('ppcart_known_post_types')) {
            $types = array_merge(
                ppcart_known_post_types('product'),
                ppcart_known_post_types('order'),
                ppcart_known_post_types('subscription')
            );
        }
        if (function_exists('ppcart_known_pro_post_types')) {
            $types = array_merge(
                $types,
                ppcart_known_pro_post_types('us_path'),
                ppcart_known_pro_post_types('collection'),
                ppcart_known_pro_post_types('upgrade_path')
            );
        }

        $types = array_values(array_unique(array_filter($types)));

        return $types ? $types : [
            self::POST_TYPE_PRODUCT,
            self::POST_TYPE_ORDER,
            self::POST_TYPE_SUBSCRIPTION,
            self::POST_TYPE_UPSELL_PATH,
            self::POST_TYPE_COLLECTION,
            self::POST_TYPE_UPGRADE_PATH,
        ];
    }

    /**
     * Builds the admin hook suffix for a Cart submenu page.
     *
     * @param string $page_slug Submenu page slug.
     * @return string
     */
    public static function submenu_hook_suffix($page_slug)
    {
        return self::MENU_HOOK_PREFIX . self::normalize_key($page_slug);
    }

    /**
     * Removes a submenu entry from the Cart admin menu.
     *
     * @param string $submenu_slug Submenu page slug.
     * @return void
     */
    public static function remove_submenu_page($submenu_slug)
    {
        if (! function_exists('remove_submenu_page')) {
            return;
        }

        $submenu_slug = self::normalize_key($submenu_slug);
        remove_submenu_page(self::menu_slug(), $submenu_slug);
    }

    /**
     * Clears the cached request context.
     *
     * @return void
     */
    public static function reset_cache()
    {
        // Kept for isolated tests and any external callers from earlier helper versions.
    }

    /**
     * Checks whether the current request belongs to a PublishPress Cart admin screen.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_plugin_screen($hook_suffix = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/screens-is-plugin-screen.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Checks whether the current request is the settings screen.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_settings_screen($hook_suffix = '')
    {
        return self::is_submenu_page_screen(self::PAGE_SETTINGS, $hook_suffix);
    }

    /**
     * Checks whether the current request is the top-level dashboard screen.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_dashboard_screen($hook_suffix = '')
    {
        return self::is_page_screen(self::PAGE_DASHBOARD, self::HOOK_DASHBOARD, $hook_suffix);
    }

    /**
     * Checks whether the current request is the contacts screen.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_contacts_screen($hook_suffix = '')
    {
        return self::is_submenu_page_screen(self::PAGE_CONTACTS, $hook_suffix);
    }

    /**
     * Checks whether the current request is the customer reports screen.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_customer_reports_screen($hook_suffix = '')
    {
        return self::is_submenu_page_screen(self::PAGE_CUSTOMER_REPORTS, $hook_suffix);
    }

    /**
     * Checks whether the current request is a reports screen.
     *
     * @param string $hook_suffix             Current admin hook suffix.
     * @param bool   $include_customer_reports Whether to include the customer reports screen.
     * @return bool
     */
    public static function is_reports_screen($hook_suffix = '', $include_customer_reports = true)
    {
        if (self::is_submenu_page_screen(self::PAGE_REPORTS, $hook_suffix)) {
            return true;
        }

        return $include_customer_reports && self::is_customer_reports_screen($hook_suffix);
    }

    /**
     * Checks whether the current request is the white-label screen.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_white_label_screen($hook_suffix = '')
    {
        return self::is_submenu_page_screen(self::PAGE_WHITE_LABEL, $hook_suffix);
    }

    /**
     * Checks whether the current request is an order/contact/customer-report UI screen.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_order_contact_or_customer_report_screen($hook_suffix = '')
    {
        return self::is_post_type_screen(
            function_exists('ppcart_known_post_types')
                ? array_merge(
                    ppcart_known_post_types('product'),
                    ppcart_known_post_types('order'),
                    ppcart_known_post_types('subscription')
                )
                : [
                    self::POST_TYPE_PRODUCT,
                    self::POST_TYPE_ORDER,
                    self::POST_TYPE_SUBSCRIPTION,
                ],
            $hook_suffix
        ) || self::is_contacts_screen($hook_suffix) || self::is_customer_reports_screen($hook_suffix);
    }

    /**
     * Checks whether the current request uses the modern Cart admin shell.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_modern_admin_ui_screen($hook_suffix = '')
    {
        return self::is_post_type_screen(
            self::modern_admin_post_types(),
            $hook_suffix
        )
        || self::is_contacts_screen($hook_suffix)
        || self::is_customer_reports_screen($hook_suffix)
        || self::is_submenu_page_screen(self::PAGE_EXTENSIONS, $hook_suffix)
        || self::is_submenu_page_screen(self::PAGE_AFFILIATES, $hook_suffix);
    }

    /**
     * Checks whether the current request targets one of the given post types.
     *
     * @param array|string $post_types Post type or post types.
     * @param string       $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_post_type_screen($post_types, $hook_suffix = '')
    {
        $context    = self::get_context($hook_suffix);
        $post_types = self::normalize_list((array) $post_types);

        if (! $context['has_screen']) {
            return in_array($context['post_type'], $post_types, true);
        }

        return in_array($context['screen_post_type'], $post_types, true) || in_array($context['current_post_type'], $post_types, true);
    }

    /**
     * Checks whether the current request targets one of the given taxonomies.
     *
     * @param array|string $taxonomies Taxonomy or taxonomies.
     * @param string       $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_taxonomy_screen($taxonomies, $hook_suffix = '')
    {
        $context    = self::get_context($hook_suffix);
        $taxonomies = self::normalize_list((array) $taxonomies);

        if (! $context['has_screen']) {
            return in_array($context['taxonomy'], $taxonomies, true);
        }

        return in_array($context['screen_taxonomy'], $taxonomies, true);
    }

    /**
     * Checks whether version notices should target the current request.
     *
     * The single product editor is deliberately excluded to preserve existing behavior.
     *
     * @param string $hook_suffix Current admin hook suffix.
     * @return bool
     */
    public static function is_version_notice_screen($hook_suffix = '')
    {
        $context = self::get_context($hook_suffix);

        $product_types = function_exists('ppcart_known_post_types')
            ? ppcart_known_post_types('product')
            : [ self::POST_TYPE_PRODUCT ];
        if ($context['has_screen'] && 'post' === $context['screen_base'] && in_array($context['screen_post_type'], $product_types, true)) {
            return false;
        }

        return self::is_plugin_screen($hook_suffix);
    }

    /**
     * Checks whether the current request is a standalone log viewer route.
     *
     * @return bool
     */
    public static function is_standalone_log_viewer_route()
    {
        $context = self::get_context();
        return ! empty($context['route_params']);
    }
}
