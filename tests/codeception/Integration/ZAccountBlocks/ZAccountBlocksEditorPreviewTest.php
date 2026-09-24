<?php

declare(strict_types=1);

namespace Tests\Integration\CheckoutBlock;

use Tests\Support\Integration\CheckoutBlockTestCase;

/**
 * Editor preview tests define REST_REQUEST; live under ZAccountBlocks so
 * other integration tests keep a frontend (non-REST) process.
 */
class ZAccountBlocksEditorPreviewTest extends CheckoutBlockTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_157_account_orders_block_shows_sample_data_in_block_editor_preview(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account editor preview checks require creating a temporary account user.');
        }

        global $wp;

        $previousRestRoute  = is_object($wp) && isset($wp->query_vars['rest_route'])
            ? $wp->query_vars['rest_route']
            : null;
        $previousRequestUri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI']))
            : null;

        if (! defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        wp_set_current_user($this->temporaryUserId);

        if (is_object($wp)) {
            $wp->query_vars['rest_route'] = '/wp/v2/block-renderer/publishpress-cart/account-orders';
        }
        $_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/block-renderer/publishpress-cart/account-orders';

        try {
            $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-orders /-->');

            $this->assertStringContainsString('Sample Product', $output);
            $this->assertStringNotContainsString('Account Block Order', $output);
        } finally {
            if (is_object($wp)) {
                if (null === $previousRestRoute) {
                    unset($wp->query_vars['rest_route']);
                } else {
                    $wp->query_vars['rest_route'] = $previousRestRoute;
                }
            }

            if (null === $previousRequestUri) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousRequestUri;
            }
        }
    }

    public function test_IT_158_nested_account_tab_blocks_show_sample_data_in_block_editor_preview(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account editor preview checks require creating a temporary account user.');
        }

        global $wp;

        $previousRestRoute  = is_object($wp) && isset($wp->query_vars['rest_route'])
            ? $wp->query_vars['rest_route']
            : null;
        $previousRequestUri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI']))
            : null;

        if (! defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        wp_set_current_user($this->temporaryUserId);

        if (is_object($wp)) {
            $wp->query_vars['rest_route'] = '/wp/v2/block-renderer/publishpress-cart/account-orders';
        }
        $_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/block-renderer/publishpress-cart/account-orders';

        try {
            $output = $this->renderBlocks(
                '<!-- wp:publishpress-cart/account-navigation -->'
                . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} -->'
                . '<!-- wp:publishpress-cart/account-orders /-->'
                . '<!-- /wp:publishpress-cart/account-tab -->'
                . '<!-- /wp:publishpress-cart/account-navigation -->'
            );

            $this->assertStringContainsString('Sample Product', $output);
            $this->assertStringNotContainsString('Account Block Order', $output);
        } finally {
            if (is_object($wp)) {
                if (null === $previousRestRoute) {
                    unset($wp->query_vars['rest_route']);
                } else {
                    $wp->query_vars['rest_route'] = $previousRestRoute;
                }
            }

            if (null === $previousRequestUri) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousRequestUri;
            }
        }
    }

    public function test_IT_159_account_downloads_block_shows_sample_data_in_block_editor_preview(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account editor preview checks require creating a temporary account user.');
        }

        global $wp;

        $previousRestRoute  = is_object($wp) && isset($wp->query_vars['rest_route'])
            ? $wp->query_vars['rest_route']
            : null;
        $previousRequestUri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI']))
            : null;

        if (! defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        wp_set_current_user($this->temporaryUserId);

        if (is_object($wp)) {
            $wp->query_vars['rest_route'] = '/wp/v2/block-renderer/publishpress-cart/account-orders';
        }
        $_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/block-renderer/publishpress-cart/account-orders';

        try {
            $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-downloads /-->');

            $this->assertStringContainsString('User Guide.pdf', $output);
        } finally {
            if (is_object($wp)) {
                if (null === $previousRestRoute) {
                    unset($wp->query_vars['rest_route']);
                } else {
                    $wp->query_vars['rest_route'] = $previousRestRoute;
                }
            }

            if (null === $previousRequestUri) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousRequestUri;
            }
        }
    }

    public function test_IT_160_account_orders_editor_preview_still_enforces_ownership_on_detail_routes(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryOtherOrderId) {
            $this->markTestSkipped('Account orders editor preview route ownership check requires creating a second temporary order.');
        }

        global $wp;

        $previousRestRoute  = is_object($wp) && isset($wp->query_vars['rest_route'])
            ? $wp->query_vars['rest_route']
            : null;
        $previousRequestUri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI']))
            : null;

        if (! defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        wp_set_current_user($this->temporaryUserId);

        if (is_object($wp)) {
            $wp->query_vars['rest_route'] = '/wp/v2/block-renderer/publishpress-cart/account-orders';
        }

        $editorBlockRequestUri = '/wp-json/wp/v2/block-renderer/publishpress-cart/account-orders';

        $output = $this->withRequestContext(
            array('ppcart-order' => (string) $this->temporaryOtherOrderId),
            $editorBlockRequestUri . '?ppcart-order=' . rawurlencode((string) $this->temporaryOtherOrderId),
            function () use ($wp, $previousRestRoute) {
                if (is_object($wp)) {
                    $wp->query_vars['rest_route'] = '/wp/v2/block-renderer/publishpress-cart/account-orders';
                }

                return $this->renderBlocks('<!-- wp:publishpress-cart/account-orders /-->');
            }
        );

        if (is_object($wp)) {
            if (null === $previousRestRoute) {
                unset($wp->query_vars['rest_route']);
            } else {
                $wp->query_vars['rest_route'] = $previousRestRoute;
            }
        }

        $this->assertStringContainsString('You do not have permission to access this account content.', $output);
        $this->assertStringNotContainsString('Other Account Block Order', $output);
        $this->assertStringNotContainsString('Sample Product', $output);
    }
}
