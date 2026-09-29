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
        add_action('admin_head', [ __CLASS__, 'maybe_print_pro_upgrade_notice_styles' ]);
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
     * Prints banner styles on Cart admin screens when the banner may show.
     *
     * @return void
     */
    public static function maybe_print_pro_upgrade_notice_styles()
    {
        if (! self::should_display_pro_upgrade_top_notice()) {
            return;
        }

        ?>
        <style>
            .pp-version-notice-bold-purple {
                background: #655997;
                height: auto;
                box-sizing: border-box;
                padding: 10px 40px;
                text-align: center;
                position: relative;
                overflow: hidden;
                line-height: 20px;
                margin-left: -20px;
                font-size: 14px;
                color: #ffffff;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                text-align: center;
                vertical-align: middle;
                justify-content: space-between;
            }

            .pp-version-notice-bold-purple-message {
                width: 90%;
                text-align: center;
                margin-right: 20px;
            }

            .pp-version-notice-bold-purple-button {
                background: #FEB123;
                color: #000000 !important;
                font-weight: normal;
                text-decoration: none;
                padding: 6px 10px;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                box-sizing: border-box;
                border: 1px solid #fca871;
                break-inside: avoid;
                white-space: nowrap;
                max-width: 170px;
            }

            .pp-version-notice-bold-purple-button:hover {
                background: #fcca46;
                color:#181818 !important;
            }

            .pp-version-notice-bold-purple-button:active {
                background: #FEB123;
                color: #000000 !important;
            }

            .pp-version-notice-bold-purple-button a {
                text-decoration: none !important;
                color: #414141 !important;
            }

            .pp-version-notice-bold-purple-dismiss {
                color: #ffffff !important;
                margin-left: 12px;
                text-decoration: underline;
                white-space: nowrap;
            }

            @media only screen and (max-width: 600px) {
                .pp-version-notice-bold-purple {
                    padding: 55px 15px 10px 15px;
                }

                .pp-version-notice-bold-purple-message {
                    width: 62%;
                    text-align: center;
                    margin-right: 10px;
                    margin-left: 10px;
                }

                .pp-version-notice-bold-purple-button {
                    width: 38%;
                    text-align: center;
                }
            }
        </style>
        <?php
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
