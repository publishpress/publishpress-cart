<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shows admin bar warnings when enabled payment processors are running in test mode.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Admin_Test_Mode_Notice_Controller
{
    /** @var string */
    private $plugin_name;

    /** @var string */
    private $plugin_title;

    /** @var string */
    private $version;

    /** @var \PublishPress\Stripe\StripeClient|null */
    private $stripe;

    public function __construct($plugin_name, $plugin_title, $version)
    {
        global $ppcart_stripe;

        $this->stripe = empty($ppcart_stripe['sk']) ? null : ppcart_stripe_client($ppcart_stripe['sk']);
        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;

        add_action('admin_bar_menu', [$this, 'add_test_mode_admin_bar_notice'], 100);
        add_action('admin_head', [$this, 'print_test_mode_admin_bar_styles']);
        add_action('wp_head', [$this, 'print_test_mode_admin_bar_styles']);
    }


    private function get_test_mode_payment_processors()
    {
        $processors = [];

        if ('1' === (string) get_option('_ppcart_stripe_enable')) {
            $stripe_mode = function_exists('ppcart_normalize_stripe_mode')
                ? ppcart_normalize_stripe_mode(get_option('_ppcart_stripe_api', 'test'))
                : sanitize_text_field((string) get_option('_ppcart_stripe_api', 'test'));

            if ('test' === $stripe_mode) {
                $processors[] = 'Stripe';
            }
        }

        if ('1' === (string) get_option('_ppcart_paypal_enable')) {
            $paypal_mode = sanitize_text_field((string) get_option('_ppcart_paypal_enable_sandbox', 'enable'));

            if ('disable' !== $paypal_mode) {
                $processors[] = 'PayPal';
            }
        }

        $processors = apply_filters('ppcart_admin_bar_test_mode_processors', $processors);

        return is_array($processors) ? $processors : [];
    }


    public function add_test_mode_admin_bar_notice($wp_admin_bar)
    {
        if (
            ! is_admin_bar_showing()
            || (! ppcart_user_can('manager_option') && ! current_user_can('manage_options'))
        ) {
            return;
        }

        $processors = array_unique(array_filter(array_map(
            'sanitize_text_field',
            $this->get_test_mode_payment_processors()
        )));

        if (empty($processors)) {
            return;
        }

        $title = sprintf(
            /* translators: %s: active payment processors list. */
            __('Cart - Test Mode Active (%1$s)', 'publishpress-cart'),
            implode(' | ', $processors)
        );

        $wp_admin_bar->add_node([
            'id'     => 'ppcart-test-mode',
            'parent' => 'top-secondary',
            'title'  => esc_html($title),
            'href'   => admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '#payment_methods'),
            'meta'   => [
                'class' => 'ppcart-test-mode-admin-bar',
                'title' => __('Open PublishPress Cart payment settings', 'publishpress-cart'),
            ],
        ]);
    }


    public function print_test_mode_admin_bar_styles()
    {
        if (
            ! is_admin_bar_showing()
            || (! ppcart_user_can('manager_option') && ! current_user_can('manage_options'))
            || empty($this->get_test_mode_payment_processors())
        ) {
            return;
        }
        ppcart_enqueue_or_print_inline_style(
            'admin-bar',
            '
            #wpadminbar #wp-admin-bar-ppcart-test-mode > .ab-item {
                background: #996800;
                color: #fff;
                font-weight: 600;
                text-shadow: none;
            }

            #wpadminbar #wp-admin-bar-ppcart-test-mode > .ab-item:hover,
            #wpadminbar #wp-admin-bar-ppcart-test-mode > .ab-item:focus,
            #wpadminbar #wp-admin-bar-ppcart-test-mode > .ab-item:active,
            #wpadminbar #wp-admin-bar-ppcart-test-mode.hover > .ab-item {
                background: #7a5200;
                color: #fff;
            }

            #wpadminbar #wp-admin-bar-ppcart-test-mode > .ab-item:visited {
                color: #fff;
            }
            ',
            'ppcart-test-mode-admin-bar'
        );
    }
}
