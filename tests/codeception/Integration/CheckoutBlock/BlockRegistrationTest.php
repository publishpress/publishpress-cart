<?php

declare(strict_types=1);

namespace Tests\Integration\CheckoutBlock;

use Tests\Support\Integration\CheckoutBlockTestCase;

class BlockRegistrationTest extends CheckoutBlockTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_103_new_checkout_block_registered_under_publishpress_cart_namespace(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();

        $this->assertTrue($registry->is_registered('publishpress-cart/checkout-form'));
    }

    public function test_IT_104_legacy_checkout_block_alias_is_not_registered_by_cart(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();

        $this->assertFalse($registry->is_registered('sc-products-shortcode/product-shortcode'));
    }

    public function test_IT_105_removed_simple_account_page_block_is_not_registered(): void
    {
        $registry       = \WP_Block_Type_Registry::get_instance();
        $removedAccount = 'publishpress-cart/account-' . 'page';

        $this->assertFalse($registry->is_registered($removedAccount));
    }

    public function test_IT_106_checkout_block_registers_both_frontend_and_editor_stylesheets(): void
    {
        $this->assertTrue(wp_style_is('ppcart-checkout-form-style', 'registered'));
        $this->assertTrue(wp_style_is('ppcart-checkout-form-editor-style', 'registered'));
    }

    public function test_IT_107_checkout_block_type_wires_its_dedicated_checkout_style_handles(): void
    {
        $registry       = \WP_Block_Type_Registry::get_instance();
        $checkoutBlock  = $registry->get_registered('publishpress-cart/checkout-form');

        if (! $checkoutBlock instanceof \WP_Block_Type
            || ! property_exists($checkoutBlock, 'style_handles')
            || ! property_exists($checkoutBlock, 'editor_style_handles')) {
            $this->markTestSkipped('Checkout block style handle introspection requires WP_Block_Type style handle properties.');
        }

        $this->assertContains('ppcart-checkout-form-style', $checkoutBlock->style_handles);
        $this->assertContains('ppcart-checkout-form-editor-style', $checkoutBlock->editor_style_handles);
    }

    public function test_IT_108_checkout_block_css_is_served_from_the_gutenberg_integration_directory(): void
    {
        $this->assertStringContainsString(
            '/includes/integrations/gutenberg/css/checkout-block.css',
            $this->getRegisteredStyleSrc('ppcart-checkout-form-style')
        );
        $this->assertStringContainsString(
            '/includes/integrations/gutenberg/css/checkout-editor.css',
            $this->getRegisteredStyleSrc('ppcart-checkout-form-editor-style')
        );
    }

    public function test_IT_109_account_block_registers_both_frontend_and_editor_stylesheets(): void
    {
        $this->assertTrue(wp_style_is('ppcart-account-block-style', 'registered'));
        $this->assertTrue(wp_style_is('ppcart-account-block-editor-style', 'registered'));
    }

    public function test_IT_110_account_page_builder_block_wires_its_dedicated_account_style_handles(): void
    {
        $registry          = \WP_Block_Type_Registry::get_instance();
        $accountPageBlock  = $registry->get_registered('publishpress-cart/account-page-builder');

        if (! $accountPageBlock instanceof \WP_Block_Type
            || ! property_exists($accountPageBlock, 'style_handles')
            || ! property_exists($accountPageBlock, 'editor_style_handles')) {
            $this->markTestSkipped('Account block style handle introspection requires WP_Block_Type style handle properties.');
        }

        $this->assertContains('ppcart-account-block-style', $accountPageBlock->style_handles);
        $this->assertContains('ppcart-account-block-editor-style', $accountPageBlock->editor_style_handles);
    }

    public function test_IT_111_account_block_css_is_served_from_the_gutenberg_integration_directory(): void
    {
        $this->assertStringContainsString(
            '/includes/integrations/gutenberg/css/account-block.css',
            $this->getRegisteredStyleSrc('ppcart-account-block-style')
        );
        $this->assertStringContainsString(
            '/includes/integrations/gutenberg/css/account-editor.css',
            $this->getRegisteredStyleSrc('ppcart-account-block-editor-style')
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function accountComponentBlockProvider(): array
    {
        return array(
            'account-page-builder' => array('publishpress-cart/account-page-builder'),
            'account-navigation' => array('publishpress-cart/account-navigation'),
            'account-tab' => array('publishpress-cart/account-tab'),
            'account-orders' => array('publishpress-cart/account-orders'),
            'account-subscriptions' => array('publishpress-cart/account-subscriptions'),
            'account-payment-plans' => array('publishpress-cart/account-payment-plans'),
            'account-profile' => array('publishpress-cart/account-profile'),
            'account-login' => array('publishpress-cart/account-login'),
            'account-downloads' => array('publishpress-cart/account-downloads'),
        );
    }

    /**
     * @dataProvider accountComponentBlockProvider
     */
    public function test_IT_112_every_account_component_block_is_registered(string $blockName): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();

        $this->assertTrue(
            $registry->is_registered($blockName),
            "{$blockName} is registered."
        );
    }
}
