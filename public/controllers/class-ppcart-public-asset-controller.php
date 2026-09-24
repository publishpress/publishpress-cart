<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/traits/trait-ppcart-public-tracking-assets.php';

/**
 * Public assets and tracking output.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Registers frontend assets and purchase tracking scripts.
 */
class PPCart_Public_Asset_Controller
{
    use PPCart_Public_Tracking_Assets_Trait;

    /**
     * Public script handle.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin asset version.
     *
     * @var string
     */
    private $version;

    /**
     * Plugin meta prefix.
     *
     * @var string
     */
    public $prefix;

    /**
     * Memoized result of frontend_assets_needed().
     *
     * @var bool|null
     */
    private $frontend_assets_needed = null;

    public function __construct($plugin_name = '', $version = '', $prefix = '')
    {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        $this->prefix      = $prefix;

        add_action('ppcart_js_purchase_tracking', [$this, 'ga_purchase_tracking']);
        // Shortcode/render-time safety net for Elementor/Divi postmeta embeds.
        add_action('ppcart_enqueue_frontend_assets', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Whether the current request needs the heavy frontend assets (memoized).
     *
     * @return bool
     */
    public function frontend_assets_needed()
    {
        if (null !== $this->frontend_assets_needed) {
            return $this->frontend_assets_needed;
        }

        $this->frontend_assets_needed = $this->compute_frontend_assets_needed();

        return $this->frontend_assets_needed;
    }

    /**
     * Computes whether the current request needs the heavy frontend assets.
     *
     * @return bool
     */
    private function compute_frontend_assets_needed()
    {
        if (function_exists('ppcart_is_checkout_context') && ppcart_is_checkout_context()) {
            return true;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only routing checks.
        if (isset($_GET['ppcart-order']) || isset($_GET['ppcart-pid']) || isset($_GET['ppcart-oto']) || isset($_GET['ppcart-preview'])) {
            return true;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (isset($GLOBALS['ppcart_product']) && is_object($GLOBALS['ppcart_product'])) {
            return true;
        }

        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;

        $account_page_id = (int) get_option('_ppcart_myaccount_page_id');
        if ($account_page_id && (int) $post_id === $account_page_id) {
            return true;
        }

        global $post;
        if (is_a($post, 'WP_Post')) {
            // Postmeta-based embeds (Elementor/Divi, white-label forms) can't always be
            // detected here; ppcart_enqueue_frontend_assets in shortcode handlers covers them.
            $shortcodes = [
                'ppcart_product',
                'ppcart_plan',
                'ppcart_order_detail',
                'ppcart_order_summary_items_view',
                'ppcart_account',
                'ppcart_account_link',
                'ppcart_account_order_detail',
                'ppcart_account_subscription_detail',
                'ppcart_form',
                'ppcart_receipt',
                'ppcart_store',
                'ppcart_order_downloads',
            ];
            foreach ($shortcodes as $shortcode) {
                if (function_exists('has_shortcode') && has_shortcode($post->post_content, $shortcode)) {
                    return true;
                }
            }

            if (function_exists('has_block')) {
                $blocks = [
                    'publishpress-cart/checkout-form',
                    'publishpress-cart/account-page-builder',
                    'publishpress-cart/account-tab',
                    'publishpress-cart/account-navigation',
                    'publishpress-cart/account-orders',
                    'publishpress-cart/account-subscriptions',
                    'publishpress-cart/account-payment-plans',
                    'publishpress-cart/account-profile',
                    'publishpress-cart/account-login',
                    'publishpress-cart/account-downloads',
                ];
                foreach ($blocks as $block) {
                    if (has_block($block, $post)) {
                        return true;
                    }
                }
            }
        }

        /**
         * Allow integrations to force-load frontend assets on custom pages.
         *
         * @param bool $needed Whether the heavy frontend assets should load.
         */
        return (bool) apply_filters('ppcart_frontend_assets_needed', false);
    }

    /**
     * Enqueues heavy cart frontend assets (styles + Font Awesome + selectize).
     * Idempotent per handle. Also used as a mid-render safety net for page builders.
     *
     * @return void
     */
    public function enqueue_frontend_assets()
    {
        $public_base = plugin_dir_url(dirname(__DIR__) . '/class-ppcart-public.php');

        wp_enqueue_style('ppcart', $public_base . 'css/ppcart-public.css', [], $this->version, 'all');
        wp_enqueue_style('ppcart-selectize-default', $public_base . 'css/selectize.default.css', [], $this->version, 'all');
        wp_enqueue_style('ppcart-font-awesome-svg-with-js', PPCART_BASE_URL . 'includes/assets/font-awesome-svg-with-js.min.css', [], $this->version, 'all');
        wp_enqueue_script('ppcart-font-awesome-all', PPCART_BASE_URL . 'includes/assets/font-awesome-all.min.js', [], $this->version, true);
        wp_enqueue_script('ppcart-script-selectize', $public_base . 'js/selectize.js', [ 'jquery' ], $this->version, true);
    }

    public function enqueue_styles()
    {
        if (! $this->frontend_assets_needed()) {
            return;
        }

        $this->enqueue_frontend_assets();
    }
}
