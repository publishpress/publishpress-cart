<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Settings_Screen_Trait
{
    public function admin_body_class($classes)
    {
        if (PPCart_Admin_Screens::is_plugin_screen()) {
            $classes .= ' ' . PPCart_Admin_Screens::ADMIN_BODY_CLASS . ' ';
        }

        return $classes;
    }

    /**
     * Limits custom footer output to PublishPress Cart admin screens.
     *
     * @return bool
     */
    private function is_plugin_admin_screen()
    {
        return is_admin() && PPCart_Admin_Screens::is_plugin_screen();
    }

    /**
     * Renders the PublishPress-style footer in plugin admin screens.
     *
     * @return void
     */
    public function render_plugin_admin_footer()
    {
        if (! $this->is_plugin_admin_screen()) {
            return;
        }

        if (PPCart_Admin_Screens::is_settings_screen()) {
            return;
        }

        $this->render_plugin_admin_footer_markup();
    }

    /**
     * Renders the shared PublishPress admin footer markup.
     *
     * @param string $extra_class Optional extra class for screen-specific placement.
     *
     * @return void
     */
    public function render_plugin_admin_footer_markup($extra_class = '')
    {
        $logo_url = esc_url(PPCART_BASE_URL . 'admin/assets/publishpress-logo.png');
        $classes  = 'ppcart-pp-admin-footer';

        if (! empty($extra_class)) {
            $classes .= ' ' . sanitize_html_class($extra_class);
        }

        ?>
        <div id="ppcart-pp-admin-footer" class="<?php echo esc_attr($classes); ?>">
            <footer>
                <div class="pp-rating">
                    <a href="https://wordpress.org/support/plugin/publishpress-cart/reviews/#new-post" target="_blank"
                       rel="noopener noreferrer">
                        <?php
                        printf(
                            // translators: %1$s is the plugin name, %2$s is the star rating markup.
                            esc_html__('If you like %1$s, please leave us a %2$s rating. Thank you!', 'publishpress-cart'),
                            '<strong>' . esc_html(apply_filters('ppcart_plugin_title', $this->plugin_title)) . '</strong>',
                            '<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>'
                        );
        ?>
                    </a>
                </div>

                <hr>

                <nav>
                    <ul>
                        <li>
                            <a href="https://publishpress.com/publishpress-cart/" target="_blank" rel="noopener noreferrer"
                               title="<?php esc_attr_e('About PublishPress Cart', 'publishpress-cart'); ?>">
                                <?php esc_html_e('About', 'publishpress-cart'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(PPCART_DOCS_URL); ?>" target="_blank" rel="noopener noreferrer"
                               title="<?php esc_attr_e('PublishPress Cart Documentation', 'publishpress-cart'); ?>">
                                <?php esc_html_e('Documentation', 'publishpress-cart'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://publishpress.com/publishpress-support/" target="_blank" rel="noopener noreferrer"
                               title="<?php esc_attr_e('Contact the PublishPress team', 'publishpress-cart'); ?>">
                                <?php esc_html_e('Contact', 'publishpress-cart'); ?>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="pp-pressshack-logo">
                    <a href="https://publishpress.com" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('PublishPress', 'publishpress-cart'); ?>"/>
                    </a>
                </div>
            </footer>
        </div>
        <?php
    }

    /**
     * This function introduces the plugin options into a top-level
     * 'CreativCart' menu.
     */
    public function setup_plugin_options_menu()
    {

        global $submenu, $admin_page_hooks;

        // Register top-level and submenu pages.

        $menu_slug = PPCart_Admin_Screens::menu_slug();

        $manager_option = ppcart_live_cap('manager_option');
        $product_cat    = function_exists('ppcart_live_taxonomy') ? ppcart_live_taxonomy('product_cat') : 'ppcart_product_cat';
        $product_tag    = function_exists('ppcart_live_taxonomy') ? ppcart_live_taxonomy('product_tag') : 'ppcart_product_tag';

        add_menu_page(
            apply_filters('ppcart_plugin_title', $this->plugin_title),                  // The title to be displayed in the browser window for this page.
            apply_filters('ppcart_plugin_title', $this->plugin_title),                  // The text to be displayed for this menu item
            $manager_option,                    // Which type of users can see this menu item
            $menu_slug,         // The unique ID - that is, the slug - for this menu item
            [ $this, 'render_settings_page_content'],               // The name of the function to call when rendering this menu's page
            'dashicons-cart',
            30
        );

        // Keep screen IDs stable and uniquely prefixed while the display/menu title remains filterable.
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WordPress exposes $admin_page_hooks for menu hook-name registration; this keeps the top-level screen id prefixed.
        $admin_page_hooks[ $menu_slug ] = PPCart_Admin_Screens::PAGE_DASHBOARD;

        add_submenu_page(
            $menu_slug,
            __('Categories', 'publishpress-cart'),
            __('Categories', 'publishpress-cart'),
            $manager_option,
            'edit-tags.php?taxonomy=' . $product_cat
        );

        add_submenu_page(
            $menu_slug,
            __('Tags', 'publishpress-cart'),
            __('Tags', 'publishpress-cart'),
            $manager_option,
            'edit-tags.php?taxonomy=' . $product_tag
        );

        add_submenu_page(
            $menu_slug,
            apply_filters(
                $this->plugin_name . '-settings-page-title',
                sprintf(
                    /* translators: %s: plugin title. */
                    esc_html__('%s Settings', 'publishpress-cart'),
                    apply_filters('ppcart_plugin_title', $this->plugin_title)
                )
            ),
            apply_filters($this->plugin_name . '-settings-menu-title', esc_html__('Settings', 'publishpress-cart')),
            'manage_options',
            PPCart_Admin_Screens::PAGE_SETTINGS,
            [ $this, 'page_options' ]
        );
    }

    /**
     * This function highlights the correct top level menu item
     *when viewing a plugin taxonomy.
     */
    public function taxonomy_menu_highlight($parent_file)
    {
        if (PPCart_Admin_Screens::is_taxonomy_screen(PPCart_Admin_Screens::all_taxonomies())) {
            $parent_file = PPCart_Admin_Screens::menu_slug();
        }

        return $parent_file;
    }

    /**
     * Renders a simple page to display for the theme menu defined above.
     */
    public function render_settings_page_content($active_tab = '')
    {
        ?>
        <!-- Create a header in the default WordPress 'wrap' container -->
        <div class="wrap">
            <div class="pp-columns-wrapper pp-enable-sidebar">
                <div class="pp-column-left">

            <?php settings_errors(); ?>

            <h2><?php echo esc_html(apply_filters('ppcart_plugin_title', $this->plugin_title)); ?></h2>

            <div class="ppcart-getting-started">
                <div class="ppcart-getting-started__box postbox">
                    <div class="ppcart-getting-started__content">
                        <div class="ppcart-getting-started__content--narrow">
                            <h2><?php
                            /* translators: %s: plugin title. */
                            printf(esc_html__('Welcome to %s', 'publishpress-cart'), esc_html(apply_filters('ppcart_plugin_title', $this->plugin_title))) ?></h2>
                            <p><?php esc_html_e("To get started, head over to the settings page to connect your Stripe account and select your currency. Once that's done, you'll be ready to create your first product!", 'publishpress-cart'); ?></p>
                        </div>

                        <div class="ppcart-getting-started__actions ppcart-getting-started__content--narrow">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS)); ?>" class="button button-primary button-hero"><?php esc_html_e('Get Started!', 'publishpress-cart'); ?></a>
                        </div>
                    </div>
                </div>
            <?php
            if (class_exists('PPCart_Secrets')) {
                echo wp_kses(PPCart_Secrets::render_getting_started_encryption_html(), ppcart_admin_allowed_html());
            }
        ?>
            </div>
                </div>
                <?php if (function_exists('ppcart_render_admin_sidebar')) {
                    ppcart_render_admin_sidebar();
                } ?>
            </div>

        </div><!-- /.wrap -->
    <?php
    }

    /**
     * Creates the options page
     *
     * @since 1.0.0
     * @return      void
     */
    public function page_options()
    {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is only a route check; ppcart_check_admin_referer() validates before mutation.
        if (isset($_REQUEST['ppcart_reset_log'])) {
            if (function_exists('current_user_can') && ! current_user_can('manage_options')) {
                wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'publishpress-cart'));
            }
            ppcart_check_admin_referer('ppcart_reset_debug_log', 'ppcart_reset_debug_log_nonce');
            global $ppcart_debug_logger;
            $ppcart_debug_logger->reset_log_file();
            echo '<div id="message" class="updated fade"><p>Debug log files have been reset!</p></div>';
        }

        include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 2) . '/partials/') . 'ppcart-admin-page-settings.php');
    }
}
