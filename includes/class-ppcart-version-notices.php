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
     * Query arg for dismissing the free-to-pro top banner.
     */
    public const DISMISS_PRO_UPGRADE_QUERY_ARG = 'ppcart_dismiss_pro_upgrade_notice';

    /**
     * User-meta key when the free-to-pro top banner is dismissed.
     */
    public const DISMISS_PRO_UPGRADE_META_KEY = '_ppcart_dismiss_admin_notice_pro_upgrade';

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
        add_action('admin_init', [ __CLASS__, 'maybe_dismiss_pro_upgrade_notice' ]);
        add_action('in_admin_header', [ __CLASS__, 'maybe_render_pro_upgrade_top_notice' ]);
        add_action('admin_enqueue_scripts', [ __CLASS__, 'maybe_print_pro_upgrade_notice_styles' ]);
    }

    /**
     * Adds upgrade menu settings for wordpress-version-notices library.
     *
     * @return void
     */
    public static function register_notice_settings()
    {
        if (! self::is_pro_upgrade_notice_enabled()) {
            return;
        }

        add_filter('pp_version_notice_menu_link_settings', [ __CLASS__, 'filter_menu_link_settings' ]);
    }

    /**
     * Whether the free-to-pro upgrade top banner may render for this request.
     *
     * @return bool
     */
    public static function should_display_pro_upgrade_top_notice()
    {
        if (! self::is_pro_upgrade_notice_enabled()) {
            return false;
        }

        if (self::is_pro_upgrade_notice_dismissed()) {
            return false;
        }

        return class_exists('PPCart_Admin_Screens') && PPCart_Admin_Screens::is_version_notice_screen();
    }

    /**
     * Outputs the dismissible free-to-pro top banner markup.
     *
     * @return void
     */
    public static function render_pro_upgrade_top_notice()
    {
        $upgrade_link = 'https://publishpress.com/links/cart-banner';
        $dismiss_url  = self::get_pro_upgrade_notice_dismiss_url();

        ?>
        <div class="pp-version-notice-bold-purple">
            <div class="pp-version-notice-bold-purple-message">
                <?php
                echo esc_html__(
                    'You\'re using PublishPress Cart. The Pro version has more features and support.',
                    'publishpress-cart'
                );
                ?>
            </div>
            <div class="pp-version-notice-bold-purple-button">
                <a href="<?php echo esc_url($upgrade_link); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Upgrade to Pro', 'publishpress-cart'); ?>
                </a>
            </div>
            <a class="pp-version-notice-bold-purple-dismiss" href="<?php echo esc_url($dismiss_url); ?>">
                <?php esc_html_e('Dismiss', 'publishpress-cart'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Prints the top banner when allowed for the current screen.
     *
     * @return void
     */
    public static function maybe_render_pro_upgrade_top_notice()
    {
        if (! self::should_display_pro_upgrade_top_notice()) {
            return;
        }

        self::render_pro_upgrade_top_notice();
    }

    /**
     * Persists per-user dismissal for the free-to-pro top banner.
     *
     * @return void
     */
    public static function maybe_dismiss_pro_upgrade_notice()
    {
        if (! is_admin() || ! self::is_pro_upgrade_notice_enabled()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Route check; ppcart_check_admin_referer() validates before writing user meta.
        if (empty($_GET[ self::DISMISS_PRO_UPGRADE_QUERY_ARG ])) {
            return;
        }

        if (! function_exists('ppcart_check_admin_referer')) {
            return;
        }

        ppcart_check_admin_referer('ppcart_dismiss_pro_upgrade_notice');

        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, self::DISMISS_PRO_UPGRADE_META_KEY, '1');
        }

        wp_safe_redirect(remove_query_arg([ self::DISMISS_PRO_UPGRADE_QUERY_ARG, '_wpnonce' ]));
        exit;
    }

    /**
     * Enqueues banner styles on Cart admin screens when the banner may show.
     *
     * @return void
     */
    public static function maybe_print_pro_upgrade_notice_styles()
    {
        if (! self::should_display_pro_upgrade_top_notice()) {
            return;
        }

        wp_enqueue_style(
            'ppcart-version-notices',
            PPCART_BASE_URL . 'admin/css/ppcart-version-notices.css',
            [],
            defined('PPCART_VERSION') ? PPCART_VERSION : null
        );
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

    /**
     * Whether version-notice hooks should run for the current user.
     *
     * @return bool
     */
    private static function is_pro_upgrade_notice_enabled()
    {
        return apply_filters('ppcart_show_version_notices', true) && current_user_can('install_plugins');
    }

    /**
     * Whether the current user dismissed the free-to-pro top banner.
     *
     * @return bool
     */
    private static function is_pro_upgrade_notice_dismissed()
    {
        $user_id = get_current_user_id();
        if (! $user_id) {
            return false;
        }

        return (bool) get_user_meta($user_id, self::DISMISS_PRO_UPGRADE_META_KEY, true);
    }

    /**
     * Nonce-protected dismiss URL for the free-to-pro top banner.
     *
     * @return string
     */
    private static function get_pro_upgrade_notice_dismiss_url()
    {
        $removable = function_exists('wp_removable_query_args') ? wp_removable_query_args() : [];
        $base      = remove_query_arg(
            array_merge(
                $removable,
                [
                    'action',
                    '_wpnonce',
                    self::DISMISS_PRO_UPGRADE_QUERY_ARG,
                ]
            )
        );

        return wp_nonce_url(
            add_query_arg(self::DISMISS_PRO_UPGRADE_QUERY_ARG, '1', $base),
            'ppcart_dismiss_pro_upgrade_notice'
        );
    }
}
