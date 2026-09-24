<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * PublishPress Cart version notices integration (free plugin only).
 */
class PPCart_Version_Notices
{
    /**
     * Registers free-plugin upgrade notices.
     *
     * @return void
     */
    public static function init()
    {
        if (! is_admin() || defined('PUBLISHPRESS_CART_SKIP_VERSION_NOTICES')) {
            return;
        }

        add_action('plugins_loaded', [ __CLASS__, 'register_notice_settings' ], 20);
    }

    /**
     * Adds top notice and upgrade menu settings for wordpress-version-notices library.
     *
     * @return void
     */
    public static function register_notice_settings()
    {
        if (! apply_filters('ppcart_show_version_notices', true)) {
            return;
        }

        if (! current_user_can('install_plugins')) {
            return;
        }

        $top_notice_settings_filter = 'pp_version_notice_top_notice_settings';

        if (class_exists('\\PublishPress\\WordpressVersionNotices\\Module\\TopNotice\\Module')) {
            $top_notice_settings_filter = \PublishPress\WordpressVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER;
        } elseif (class_exists('\\PPVersionNotices\\Module\\TopNotice\\Module')) {
            $top_notice_settings_filter = \PPVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER;
        }

        add_filter($top_notice_settings_filter, [ __CLASS__, 'filter_top_notice_settings' ]);
        add_filter('pp_version_notice_menu_link_settings', [ __CLASS__, 'filter_menu_link_settings' ]);
    }

    /**
     * Injects free-to-pro top notice settings.
     *
     * @param array $settings Existing settings.
     *
     * @return array
     */
    public static function filter_top_notice_settings($settings)
    {
        if (! is_array($settings)) {
            $settings = [];
        }

        $screens = self::is_current_cart_admin_request() ? [ true ] : self::plugin_version_notice_screens();

        $settings['publishpress-cart'] = [
            // translators: %1$s and %2$s are placeholders for the Pro link markup.
            'message' => __(
                'You\'re using PublishPress Cart. The Pro version has more features and support. %1$sUpgrade to Pro%2$s',
                'publishpress-cart'
            ),
            'link' => 'https://publishpress.com/links/cart-banner',
            'screens' => $screens,
        ];

        return $settings;
    }

    /**
     * Admin screens that show the free-to-pro version notice.
     *
     * Built from live CPT/taxonomy maps so leftover and canonical slugs both match.
     *
     * @return array<int, array<string, string>>
     */
    private static function plugin_version_notice_screens()
    {
        $screens = [
            [ 'base' => PPCart_Admin_Screens::HOOK_DASHBOARD, 'id' => PPCart_Admin_Screens::HOOK_DASHBOARD ],
        ];

        $order_types        = function_exists('ppcart_known_post_types') ? ppcart_known_post_types('order') : [ 'ppcart_order' ];
        $subscription_types = function_exists('ppcart_known_post_types') ? ppcart_known_post_types('subscription') : [ 'ppcart_subscription' ];
        $product_types      = function_exists('ppcart_known_post_types') ? ppcart_known_post_types('product') : [ 'ppcart_product' ];
        $us_path_types      = function_exists('ppcart_known_pro_post_types') ? ppcart_known_pro_post_types('us_path') : [ 'ppcart_us_path' ];
        $taxonomies         = function_exists('ppcart_all_known_taxonomies') ? ppcart_all_known_taxonomies() : [ 'ppcart_product_cat', 'ppcart_product_tag' ];

        foreach (array_merge($order_types, $subscription_types) as $post_type) {
            $screens[] = [ 'base' => 'post', 'id' => $post_type, 'post_type' => $post_type ];
        }
        foreach (array_merge($product_types, $order_types, $subscription_types, $us_path_types) as $post_type) {
            $screens[] = [ 'base' => 'edit', 'id' => 'edit-' . $post_type, 'post_type' => $post_type ];
        }
        foreach ($taxonomies as $taxonomy) {
            $screens[] = [ 'base' => 'edit-tags', 'id' => 'edit-' . $taxonomy, 'taxonomy' => $taxonomy ];
            $screens[] = [ 'base' => 'term', 'id' => 'edit-' . $taxonomy, 'taxonomy' => $taxonomy ];
        }

        return $screens;
    }

    /**
     * Detects whether the current admin request belongs to PublishPress Cart.
     *
     * @return bool
     */
    private static function is_current_cart_admin_request()
    {
        return class_exists('PPCart_Admin_Screens') && PPCart_Admin_Screens::is_version_notice_screen();
    }

    /**
     * Injects free-to-pro upgrade submenu settings.
     *
     * @param array $settings Existing settings.
     *
     * @return array
     */
    public static function filter_menu_link_settings($settings)
    {
        if (! is_array($settings)) {
            $settings = [];
        }

        $settings['publishpress-cart'] = [
            'parent' => PPCart_Admin_Screens::menu_slug(),
            'label'  => __('Upgrade to Pro', 'publishpress-cart'),
            'link'   => 'https://publishpress.com/links/cart-menu',
        ];

        return $settings;
    }
}
