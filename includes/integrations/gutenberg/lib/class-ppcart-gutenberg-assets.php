<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Gutenberg_Assets
{
    public const CHECKOUT_SCRIPT_HANDLE       = 'ppcart-checkout-form-editor';
    public const CHECKOUT_STYLE_HANDLE        = 'ppcart-checkout-form-style';
    public const CHECKOUT_EDITOR_STYLE_HANDLE = 'ppcart-checkout-form-editor-style';
    public const CHECKOUT_PUBLIC_STYLE_HANDLE = 'ppcart-checkout-form-public-style';
    public const SELECTIZE_STYLE_HANDLE       = 'ppcart-selectize-default';

    public const ACCOUNT_SCRIPT_HANDLE       = 'ppcart-account-page-editor';
    public const ACCOUNT_VIEW_SCRIPT_HANDLE  = 'ppcart-account-page-view';
    public const ACCOUNT_STYLE_HANDLE        = 'ppcart-account-block-style';
    public const ACCOUNT_EDITOR_STYLE_HANDLE = 'ppcart-account-block-editor-style';

    /**
     * Gutenberg integration directory.
     *
     * @var string
     */
    private $base_dir;

    /**
     * Gutenberg index file.
     *
     * @var string
     */
    private $base_file;

    /**
     * Checkout renderer.
     *
     * @var PPCart_Checkout_Renderer
     */
    private $checkout_renderer;

    /**
     * Account renderer.
     *
     * @var PPCart_Account_Renderer
     */
    private $account_renderer;

    public function __construct($base_file, PPCart_Checkout_Renderer $checkout_renderer, PPCart_Account_Renderer $account_renderer)
    {
        $this->base_file         = $base_file;
        $this->base_dir          = dirname($base_file);
        $this->checkout_renderer = $checkout_renderer;
        $this->account_renderer  = $account_renderer;
    }

    public function register()
    {
        $this->register_checkout_assets();
        $this->register_account_assets();
    }

    public function localize_editor_assets()
    {
        $this->localize_checkout_editor_assets();
        $this->localize_account_editor_assets();
    }

    private function register_checkout_assets()
    {
        $asset_file = $this->base_dir . '/build/order-form.asset.php';
        $asset      = file_exists($asset_file) ? require $asset_file : [
            'dependencies' => [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ],
            'version'      => PPCART_VERSION,
        ];

        wp_register_script(
            self::CHECKOUT_SCRIPT_HANDLE,
            plugins_url('build/order-form.js', $this->base_file),
            $asset['dependencies'] ?? [],
            $asset['version'] ?? PPCART_VERSION,
            true
        );

        if (! wp_style_is(self::CHECKOUT_PUBLIC_STYLE_HANDLE, 'registered')) {
            wp_register_style(
                self::CHECKOUT_PUBLIC_STYLE_HANDLE,
                PPCART_BASE_URL . 'public/css/ppcart-public.css',
                [],
                PPCART_VERSION
            );
        }

        if (! wp_style_is(self::SELECTIZE_STYLE_HANDLE, 'registered')) {
            wp_register_style(
                self::SELECTIZE_STYLE_HANDLE,
                PPCART_BASE_URL . 'public/css/selectize.default.css',
                [],
                PPCART_VERSION
            );
        }

        wp_register_style(
            self::CHECKOUT_STYLE_HANDLE,
            plugins_url('css/checkout-block.css', $this->base_file),
            [],
            PPCART_VERSION
        );

        wp_register_style(
            self::CHECKOUT_EDITOR_STYLE_HANDLE,
            plugins_url('css/checkout-editor.css', $this->base_file),
            [ self::CHECKOUT_STYLE_HANDLE, self::CHECKOUT_PUBLIC_STYLE_HANDLE, self::SELECTIZE_STYLE_HANDLE ],
            PPCART_VERSION
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(self::CHECKOUT_SCRIPT_HANDLE, 'publishpress-cart', PPCART_BASE_DIR . 'languages');
        }
    }

    private function register_account_assets()
    {
        $asset_file = $this->base_dir . '/build/account-blocks.asset.php';
        $asset      = file_exists($asset_file) ? require $asset_file : [
            'dependencies' => [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ],
            'version'      => PPCART_VERSION,
        ];

        wp_register_script(
            self::ACCOUNT_SCRIPT_HANDLE,
            plugins_url('build/account-blocks.js', $this->base_file),
            $asset['dependencies'] ?? [],
            $asset['version'] ?? PPCART_VERSION,
            true
        );

        $view_asset_file = $this->base_dir . '/build/account-page-view.asset.php';
        $view_asset      = file_exists($view_asset_file) ? require $view_asset_file : [
            'dependencies' => [],
            'version'      => PPCART_VERSION,
        ];

        wp_register_script(
            self::ACCOUNT_VIEW_SCRIPT_HANDLE,
            plugins_url('build/account-page-view.js', $this->base_file),
            $view_asset['dependencies'] ?? [],
            $view_asset['version'] ?? PPCART_VERSION,
            true
        );

        wp_register_style(
            self::ACCOUNT_STYLE_HANDLE,
            plugins_url('css/account-block.css', $this->base_file),
            [],
            PPCART_VERSION
        );

        wp_register_style(
            self::ACCOUNT_EDITOR_STYLE_HANDLE,
            plugins_url('css/account-editor.css', $this->base_file),
            [ self::ACCOUNT_STYLE_HANDLE ],
            PPCART_VERSION
        );

        wp_localize_script(
            self::ACCOUNT_VIEW_SCRIPT_HANDLE,
            'publishpressCartAccountView',
            [
                'restUrl' => esc_url_raw(rest_url('publishpress-cart/v1/account-block/detail')),
                'nonce'   => wp_create_nonce('wp_rest'),
            ]
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(self::ACCOUNT_SCRIPT_HANDLE, 'publishpress-cart', PPCART_BASE_DIR . 'languages');
        }
    }

    private function localize_checkout_editor_assets()
    {
        wp_localize_script(
            self::CHECKOUT_SCRIPT_HANDLE,
            'publishpressCartCheckoutBlock',
            [
                'blockName'        => PPCart_Checkout_Renderer::BLOCK_NAME,
                'pluginTitle'      => apply_filters('ppcart_plugin_title', 'PublishPress Cart'),
                'siteTitle'        => get_bloginfo('name'),
                'restBase'         => '/publishpress-cart/v1',
                'productPostTypes' => $this->checkout_renderer->get_product_post_types(),
            ]
        );
    }

    private function localize_account_editor_assets()
    {
        wp_localize_script(
            self::ACCOUNT_SCRIPT_HANDLE,
            'publishpressCartAccountBlock',
            [
                'blockName'      => PPCart_Account_Renderer::BLOCK_NAME,
                'pluginTitle'    => apply_filters('ppcart_plugin_title', 'PublishPress Cart'),
                'navigationTabs' => $this->account_renderer->get_account_navigation_options(),
            ]
        );
    }
}
