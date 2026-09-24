<?php

declare(strict_types=1);

use Tests\Support\WordPressStubContext;

/**
 * Minimal bootstrap for unit tests that exercise checkout-completion helpers.
 */
function ppcart_unit_bootstrap_checkout_completion(): void
{
    if (! function_exists('ppcart_frontend_allowed_html')) {
        if (! WordPressStubContext::has('add_action')) {
            WordPressStubContext::set(
                'add_action',
                static function () {
                    return true;
                }
            );
        }
        if (! WordPressStubContext::has('add_filter')) {
            WordPressStubContext::set(
                'add_filter',
                static function () {
                    return true;
                }
            );
        }
        if (! WordPressStubContext::has('add_shortcode')) {
            WordPressStubContext::set(
                'add_shortcode',
                static function () {
                    return true;
                }
            );
        }
        if (! WordPressStubContext::has('apply_filters')) {
            WordPressStubContext::set(
                'apply_filters',
                static function ($hook, $value) {
                    return $value;
                }
            );
        }

        require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
    }

    if (! function_exists('ppcart_get_post_meta')) {
        require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-meta.php';
    }
    if (! class_exists('PPCart_Order', false)) {
        require_once PPCART_PLUGIN_ROOT . 'models/class-ppcart-order.php';
    }

    if (! function_exists('ppcart_checkout_completion_allowed')) {
        require_once PPCART_PLUGIN_ROOT . 'includes/functions/checkout-completion.php';
    }
}
