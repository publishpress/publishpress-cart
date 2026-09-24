<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-ppcart-checkout-renderer.php';
require_once __DIR__ . '/class-ppcart-account-context.php';
require_once __DIR__ . '/class-ppcart-account-styles.php';
require_once __DIR__ . '/class-ppcart-account-renderer.php';
require_once __DIR__ . '/class-ppcart-gutenberg-assets.php';
require_once __DIR__ . '/class-ppcart-checkout-preview-controller.php';
require_once __DIR__ . '/class-ppcart-account-detail-controller.php';

class PPCart_Gutenberg_Bootstrap
{
    /**
     * Current bootstrap instance.
     *
     * @var PPCart_Gutenberg_Bootstrap|null
     */
    private static $instance = null;

    /**
     * Gutenberg integration directory.
     *
     * @var string
     */
    private $base_dir;

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

    /**
     * Shared asset registry.
     *
     * @var PPCart_Gutenberg_Assets
     */
    private $assets;

    public function __construct($base_file = '')
    {
        self::$instance = $this;

        $base_file = $base_file ? $base_file : dirname(__DIR__) . '/index.php';

        $this->base_dir          = dirname($base_file);
        $account_context         = new PPCart_Account_Context();
        $this->checkout_renderer = new PPCart_Checkout_Renderer();
        $this->account_renderer  = new PPCart_Account_Renderer(null, $account_context);
        $this->assets            = new PPCart_Gutenberg_Assets($base_file, $this->checkout_renderer, $this->account_renderer);

        $checkout_preview_controller = new PPCart_Checkout_Preview_Controller($this->checkout_renderer);
        $account_detail_controller   = new PPCart_Account_Detail_Controller($this->account_renderer);

        add_action('init', [ $this, 'register' ]);
        add_action('enqueue_block_editor_assets', [ $this->assets, 'localize_editor_assets' ]);
        add_action('rest_api_init', [ $checkout_preview_controller, 'register_routes' ]);
        add_action('rest_api_init', [ $account_detail_controller, 'register_routes' ]);
    }

    public static function get_instance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register()
    {
        if (! function_exists('register_block_type')) {
            return;
        }

        $this->assets->register();
        $this->register_blocks();
    }

    public function get_account_renderer()
    {
        return $this->account_renderer;
    }

    private function register_blocks()
    {
        $checkout_metadata = $this->get_metadata('checkout-form');

        if (function_exists('register_block_type_from_metadata')) {
            $checkout_block_dir = $this->base_dir . '/blocks/checkout-form';
            $checkout_renderer  = $this->checkout_renderer;

            register_block_type_from_metadata(
                $checkout_block_dir,
                [
                    'render_callback' => function ($attributes, $content = '', $block = null) use ($checkout_block_dir, $checkout_renderer) {
                        $renderer = $checkout_renderer;

                        return require $checkout_block_dir . '/render.php';
                    },
                ]
            );
        } else {
            register_block_type(
                PPCart_Checkout_Renderer::BLOCK_NAME,
                [
                    'api_version'     => 3,
                    'attributes'      => $checkout_metadata['attributes'] ?? [],
                    'editor_script'   => PPCart_Gutenberg_Assets::CHECKOUT_SCRIPT_HANDLE,
                    'editor_style'    => PPCart_Gutenberg_Assets::CHECKOUT_EDITOR_STYLE_HANDLE,
                    'style'           => PPCart_Gutenberg_Assets::CHECKOUT_STYLE_HANDLE,
                    'render_callback' => [ $this->checkout_renderer, 'render' ],
                    'supports'        => $checkout_metadata['supports'] ?? [ 'html' => false ],
                ]
            );
        }

        foreach ($this->get_account_block_slugs() as $block_slug) {
            $this->register_account_block($block_slug);
        }
    }

    private function register_account_block($block_slug)
    {
        $block_dir = $this->base_dir . '/blocks/' . $block_slug;
        $renderer  = $this->account_renderer;

        if (function_exists('register_block_type_from_metadata')) {
            register_block_type_from_metadata(
                $block_dir,
                [
                    'render_callback' => function ($attributes, $content = '', $block = null) use ($block_dir, $renderer) {
                        return require $block_dir . '/render.php';
                    },
                ]
            );
            return;
        }

        $metadata = $this->get_metadata($block_slug);

        if (empty($metadata['name'])) {
            return;
        }

        $block_name = $metadata['name'];

        register_block_type(
            $block_name,
            [
                'api_version'     => 3,
                'attributes'      => $metadata['attributes'] ?? [],
                'editor_script'   => PPCart_Gutenberg_Assets::ACCOUNT_SCRIPT_HANDLE,
                'editor_style'    => PPCart_Gutenberg_Assets::ACCOUNT_EDITOR_STYLE_HANDLE,
                'style'           => PPCart_Gutenberg_Assets::ACCOUNT_STYLE_HANDLE,
                'render_callback' => function ($attributes, $content = '', $block = null) use ($block_dir, $renderer) {
                    return require $block_dir . '/render.php';
                },
                'supports'        => $metadata['supports'] ?? [ 'html' => false ],
            ]
        );
    }

    private function get_account_block_slugs()
    {
        return [
            'account-page-builder',
            'account-navigation',
            'account-tab',
            'account-orders',
            'account-subscriptions',
            'account-payment-plans',
            'account-profile',
            'account-login',
            'account-downloads',
        ];
    }

    private function get_metadata($block_slug)
    {
        $metadata_file = $this->base_dir . '/blocks/' . $block_slug . '/block.json';

        if (! file_exists($metadata_file)) {
            return [];
        }

        $metadata = wp_json_file_decode($metadata_file, ['associative' => true]);

        return is_array($metadata) ? $metadata : [];
    }
}
