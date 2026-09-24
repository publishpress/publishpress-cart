<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The extension-specific functionality of the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

/**
 * The extension-specific functionality of the plugin.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Extension_Page
{
    public const NOTICE_QUERY_ARG = 'ppcart_extension_notice';

    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The Nice Name of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_title    The Nice Name of this plugin.
     */
    private $plugin_title;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $version    The current version of this plugin.
     */
    private $version;

    /** @var string The name of the product (plugin) using this licensing client */
    private $product_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $plugin_title, $version, $product_name)
    {
        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;
        $this->product_name = $product_name;
    }

    /**
     * This function introduces the plugin options into a top-level
     * 'CreativCart' menu.
     */
    public function setup_plugin_options_menu()
    {
        add_submenu_page(
            PPCart_Admin_Screens::menu_slug(),
            apply_filters($this->plugin_name . '-settings-page-title', esc_html__('Extensions', 'publishpress-cart')),
            apply_filters($this->plugin_name . '-settings-menu-title', esc_html__('Extensions', 'publishpress-cart')),
            ppcart_live_cap('manager_option'),
            PPCart_Admin_Screens::PAGE_EXTENSIONS,
            [ $this, 'render_page_contacts' ]
        );

        add_action('admin_notices', [ $this, 'render_extension_notice' ]);
    }

    /**
     * Returns the static list of available extensions.
     *
     * @return array[]
     */
    private function get_extensions()
    {
        $assets_url = plugin_dir_url(dirname(__FILE__)) . 'admin/assets/extensions/';
        $extensions = include __DIR__ . '/templates/extensions-list.php';

        /**
         * Filters Cart extensions list shown on the Extensions admin page.
         *
         * @param array $extensions Extension cards.
         */
        return apply_filters('ppcart_extensions_list', $extensions);
    }

    /**
     * Returns the card action configuration for a single extension.
     *
     * @param array $product Extension card data.
     *
     * @return array
     */
    private function get_extension_action($product)
    {
        $action = [
            'label' => __('Upgrade to Pro', 'publishpress-cart'),
            'url'   => $product['url'] ?? 'https://publishpress.com/publishpress-cart/',
            'class' => 'product-card__download',
            'new_tab' => true,
        ];

        /**
         * Filters action button config for an extension card.
         *
         * @param array $action  Action config (label/url/class).
         * @param array $product Extension card data.
         */
        $action = apply_filters('ppcart_extension_action', $action, $product);

        if (! is_array($action)) {
            return [
                'label' => __('Upgrade to Pro', 'publishpress-cart'),
                'url'   => 'https://publishpress.com/publishpress-cart/',
                'class' => 'product-card__download',
                'new_tab' => true,
            ];
        }

        return wp_parse_args(
            $action,
            [
                'label' => __('Upgrade to Pro', 'publishpress-cart'),
                'url'   => 'https://publishpress.com/publishpress-cart/',
                'class' => 'product-card__download',
                'new_tab' => true,
            ]
        );
    }

    /**
     * Displays activation feedback after toggling an extension.
     *
     * @return void
     */
    public function render_extension_notice()
    {
        if (! is_admin()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice routing.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if (PPCart_Admin_Screens::PAGE_EXTENSIONS !== $page) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice rendering.
        $notice = isset($_GET[ self::NOTICE_QUERY_ARG ]) ? sanitize_key(wp_unslash($_GET[ self::NOTICE_QUERY_ARG ])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice rendering.
        $slug = isset($_GET['ppcart_extension_slug']) ? sanitize_key(wp_unslash($_GET['ppcart_extension_slug'])) : '';

        if ('' === $notice) {
            return;
        }

        $messages = [
            'activated' => [
                'class'   => 'notice notice-success is-dismissible',
                'message' => $this->get_extension_notice_message($slug, 'activated'),
            ],
            'deactivated' => [
                'class'   => 'notice notice-success is-dismissible',
                'message' => $this->get_extension_notice_message($slug, 'deactivated'),
            ],
            'activation-failed' => [
                'class'   => 'notice notice-error is-dismissible',
                'message' => $this->get_extension_notice_message($slug, 'activation-failed'),
            ],
            'deactivation-failed' => [
                'class'   => 'notice notice-error is-dismissible',
                'message' => $this->get_extension_notice_message($slug, 'deactivation-failed'),
            ],
        ];

        if (! isset($messages[ $notice ])) {
            return;
        }

        printf(
            '<div class="%1$s"><p>%2$s</p></div>',
            esc_attr($messages[ $notice ]['class']),
            esc_html($messages[ $notice ]['message'])
        );
    }

    /**
     * Returns a slug-specific notice message when available.
     *
     * @param string $slug   Extension slug.
     * @param string $notice Notice type.
     *
     * @return string
     */
    private function get_extension_notice_message($slug, $notice)
    {
        $messages = include __DIR__ . '/templates/extension-notice-messages.php';

        if (isset($messages[ $slug ][ $notice ])) {
            return $messages[ $slug ][ $notice ];
        }

        $fallbacks = [
            'activated'           => __('Extension activated successfully.', 'publishpress-cart'),
            'deactivated'         => __('Extension deactivated successfully.', 'publishpress-cart'),
            'activation-failed'   => __('Extension activation failed.', 'publishpress-cart'),
            'deactivation-failed' => __('Extension deactivation failed.', 'publishpress-cart'),
        ];

        return $fallbacks[ $notice ] ?? '';
    }

    /**
     * Renders a simple page to display for the theme menu defined above.
     */
    public function render_page_contacts($active_tab = '')
    {
        include __DIR__ . '/templates/extensions-page.php';
    }
}
