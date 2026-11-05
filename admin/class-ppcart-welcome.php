<?php

defined('ABSPATH') || exit;

class PPCart_Welcome
{
    private const PAGE = 'ppcart-welcome';

    public static function queue_redirect($network_wide)
    {
        global $pagenow;

        // Core verifies the activation nonce. Only queue for the single-plugin admin flow.
        $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($network_wide || is_network_admin() || wp_doing_ajax() || 'plugins.php' !== $pagenow || 'activate' !== $action) {
            return;
        }

        set_transient(self::redirect_key(), true, MINUTE_IN_SECONDS);
    }

    public static function maybe_redirect()
    {
        global $pagenow;

        if ('plugins.php' !== $pagenow || is_network_admin() || wp_doing_ajax() || ! ppcart_user_can('manager_option')) {
            return;
        }

        if (get_transient(self::redirect_key())) {
            delete_transient(self::redirect_key());
            if (wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE))) {
                exit;
            }
        }
    }

    private static function redirect_key()
    {
        return 'ppcart_welcome_redirect_' . get_current_user_id();
    }

    public static function register_page()
    {
        $title = __('Welcome to Cart', 'publishpress-cart');
        $hook = add_submenu_page(PPCart_Admin_Screens::menu_slug(), $title, $title, ppcart_live_cap('manager_option'), self::PAGE, [self::class, 'render']);
        if ($hook) {
            add_action('load-' . $hook, [self::class, 'enqueue_styles']);
            // Keep the submenu registered until WordPress has resolved the page.
            add_action('admin_head', static function () {
                remove_submenu_page(PPCart_Admin_Screens::menu_slug(), self::PAGE);
            });
        }
    }

    public static function enqueue_styles()
    {
        wp_enqueue_style('ppcart-welcome', plugins_url('css/ppcart-welcome.css', __FILE__), ['dashicons'], PPCART_VERSION);
    }

    public static function render()
    {
        if (! ppcart_user_can('manager_option')) {
            return;
        }

        $dashboard = admin_url('admin.php?page=' . PPCart_Admin_Screens::menu_slug());
        $new_product = add_query_arg('post_type', ppcart_live_post_type('product'), admin_url('post-new.php'));
        $payments = admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '#payment_methods');
        $support = 'https://publishpress.com/publishpress-support/';
        $show_brand = apply_filters('ppcart_show_admin_branding', true);
        $cards = [
            ['dashicons-products', __('Products', 'publishpress-cart'), __('Add a digital product and set its price.', 'publishpress-cart'), $new_product],
            ['dashicons-money-alt', __('Payments', 'publishpress-cart'), __('Choose how customers pay at checkout.', 'publishpress-cart'), $payments],
            ['dashicons-book-alt', __('Getting started guide', 'publishpress-cart'), __('Learn how to publish and share a checkout page.', 'publishpress-cart'), PPCART_DOCS_URL],
        ];
        ?>
        <div class="wrap ppcart-welcome">
            <header class="ppcart-welcome__header">
                <div class="ppcart-welcome__brand">
                    <?php if ($show_brand) : ?>
                        <img src="<?php echo esc_url(plugins_url('assets/publishpress-logo.png', __FILE__)); ?>" alt="<?php esc_attr_e('PublishPress', 'publishpress-cart'); ?>">
                    <?php endif; ?>
                    <strong><?php echo esc_html(apply_filters('ppcart_plugin_title', __('Cart', 'publishpress-cart'))); ?></strong>
                </div>
                <?php if ($show_brand) : ?>
                    <a class="ppcart-welcome__secondary" href="<?php echo esc_url($support); ?>"><?php esc_html_e('Get support', 'publishpress-cart'); ?></a>
                <?php endif; ?>
            </header>
            <section class="ppcart-welcome__hero">
                <div>
                    <p class="ppcart-welcome__eyebrow"><?php esc_html_e('Welcome to Cart', 'publishpress-cart'); ?></p>
                    <h1><?php esc_html_e('Start selling from your WordPress site', 'publishpress-cart'); ?></h1>
                    <p class="ppcart-welcome__lead"><?php esc_html_e('Create a product, set its price, and share its checkout page with your customers.', 'publishpress-cart'); ?></p>
                    <div class="ppcart-welcome__actions">
                        <a class="ppcart-welcome__primary" href="<?php echo esc_url($new_product); ?>"><?php esc_html_e('Create your first product', 'publishpress-cart'); ?></a>
                        <a href="<?php echo esc_url($dashboard); ?>"><?php esc_html_e('Go to dashboard', 'publishpress-cart'); ?></a>
                    </div>
                </div>
                <div class="ppcart-welcome__art" aria-hidden="true">
                    <div class="ppcart-welcome__mock">
                        <div class="ppcart-welcome__mock-top"><i></i><i></i><i></i></div>
                        <div class="ppcart-welcome__mock-body">
                            <span class="dashicons dashicons-cart"></span>
                            <b></b><i></i><i></i><em></em>
                        </div>
                    </div>
                    <span class="ppcart-welcome__check dashicons dashicons-yes"></span>
                </div>
            </section>
            <section class="ppcart-welcome__setup">
                <h2><?php esc_html_e('Set up your store', 'publishpress-cart'); ?></h2>
                <div class="ppcart-welcome__grid">
                    <?php foreach ($cards as [$icon, $label, $description, $url]) : ?>
                        <a class="ppcart-welcome__card" href="<?php echo esc_url($url); ?>">
                            <span class="dashicons <?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
                            <h3><?php echo esc_html($label); ?></h3>
                            <p><?php echo esc_html($description); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <?php
    }
}
