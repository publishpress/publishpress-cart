<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartPresentationTest extends ShoppingCartTestCase
{
    protected function tearDown(): void
    {
        foreach (['_ppcart_shopping_cart_enable', '_ppcart_after_add_to_cart', '_ppcart_cart_page_id', '_ppcart_shop_page_id', '_ppcart_cart_checkout_page_id'] as $option) {
            delete_option($option);
        }
        remove_all_actions('ppcart_shopping_cart_after_totals');
        parent::tearDown();
    }

    /** @test-id IT-440 */
    public function test_IT_440_the_mini_cart_is_a_cacheable_shell_and_disabled_cart_renders_nothing(): void
    {
        $_COOKIE['ppcart_cart_count'] = '9000';
        $shell = ppcart_render_mini_cart();
        $this->assertStringContainsString('data-ppcart-open-drawer', $shell);
        $this->assertStringContainsString('data-ppcart-cart-count>0</span>', $shell);
        $this->assertStringNotContainsString('9000', $shell);
        $this->assertSame(0, $this->rowCount());
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);
        $link = ppcart_render_mini_cart(['opensDrawer' => false, 'showCount' => false, 'styleSettings' => ['accentColor' => '#2271b1']]);
        $this->assertStringNotContainsString('data-ppcart-open-drawer', $link);
        $this->assertStringNotContainsString('data-ppcart-cart-count', $link);
        $this->assertStringContainsString('--ppcart-accent:#2271b1;', $link);
        $this->assertSame($shell, do_shortcode('[ppcart_mini_cart]'));
        $this->assertSame($shell, render_block(['blockName' => 'publishpress-cart/mini-cart', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []]));
        update_option('_ppcart_shopping_cart_enable', '0');
        $this->assertSame('', ppcart_render_mini_cart());
        unset($_COOKIE['ppcart_cart_count']);
    }

    /** @test-id IT-440 */
    public function test_IT_440_shop_forms_are_live_and_each_plan_has_its_own_in_cart_state(): void
    {
        $id = $this->product('Writer Toolkit', [$this->plan('standard:personal', '39'), $this->plan('team', '59')]);
        $before = ppcart_render_shop();
        $this->assertStringContainsString('type="submit"', $before);
        $this->assertSame(0, $this->rowCount());
        $this->add($id, 'standard:personal');
        $html = ppcart_render_shop();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($document);
        $options = $xpath->query('//li[@data-ppcart-product="' . $id . '"]//option');
        $this->assertSame('true', $options->item(0)->getAttribute('data-ppcart-in-cart'));
        $this->assertSame('false', $options->item(1)->getAttribute('data-ppcart-in-cart'));
        $this->assertStringContainsString('✓ In cart', $html);
        $this->assertStringContainsString('standard:personal', $html);
    }

    /** @test-id IT-440 */
    public function test_IT_440_post_fallback_checks_nonces_uses_server_prices_and_redirects_per_setting(): void
    {
        $cartPage = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $checkoutPage = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        update_option('_ppcart_cart_page_id', $cartPage);
        update_option('_ppcart_cart_checkout_page_id', $checkoutPage);
        $id = $this->product('Writer Toolkit', [$this->plan('standard:personal', '39')]);
        $controller = new \PPCart_Public_Shopping_Cart_Controller();
        $post = ['ppcart_add_to_cart' => $id, 'plan_id' => 'standard:personal', 'unit_price' => '0.01'];
        $this->assertSame('ppcart_cart_invalid_nonce', $controller->handle_post($post)->get_error_code());
        $this->assertSame(0, $this->rowCount());
        $post['ppcart_shopping_cart_nonce'] = wp_create_nonce('ppcart_add_to_cart');
        $this->assertSame(get_permalink($cartPage), $controller->handle_post($post));
        $this->assertSame(39.0, ppcart_get_shopping_cart()->subtotal());
        $this->assertSame(get_permalink($cartPage), $controller->handle_post($post));
        $this->assertSame(1, ppcart_get_shopping_cart()->count());
        update_option('_ppcart_after_add_to_cart', 'cart_page');
        $this->assertSame(get_permalink($cartPage), $controller->handle_post($post));
        update_option('_ppcart_after_add_to_cart', 'checkout');
        $this->assertSame(get_permalink($checkoutPage), $controller->handle_post($post));
        $remove = ['ppcart_remove_from_cart' => $id . ':standard:personal', 'ppcart_shopping_cart_nonce' => wp_create_nonce('ppcart_add_to_cart')];
        $this->assertSame('ppcart_cart_invalid_nonce', $controller->handle_post($remove)->get_error_code());
        $this->assertSame(1, ppcart_get_shopping_cart()->count());
        $remove['ppcart_shopping_cart_nonce'] = wp_create_nonce('ppcart_remove_from_cart');
        $this->assertSame(get_permalink($cartPage), $controller->handle_post($remove));
        $this->assertTrue(ppcart_get_shopping_cart()->is_empty());
    }

    /** @test-id IT-440 */
    public function test_IT_440_drawer_fragments_share_prices_notices_remove_forms_and_the_extension_slot(): void
    {
        $id = $this->product('Writer <Toolkit>', [$this->plan('standard:personal', '39')]);
        update_post_meta($id, '_ppcart_product_name', 'Writer <Toolkit>');
        $this->add($id, 'standard:personal');
        update_post_meta($id, '_ppcart_pay_options', [$this->plan('standard:personal', '49')]);
        $cart = ppcart_get_shopping_cart();
        $seen = null;
        add_action('ppcart_shopping_cart_after_totals', static function ($value) use (&$seen): void {
            $seen = $value;
            echo '<span>Extension field</span>';
        });
        $request = new \WP_REST_Request('GET', '/ppcart/v1/cart');
        $request->set_param('fragments', 'drawer,unknown,../../secret');
        $response = (new \PPCart_Shopping_Cart_View())->response($cart, $request);
        $this->assertSame(['drawer'], array_keys($response['fragments']));
        $html = $response['fragments']['drawer'];
        $this->assertStringContainsString('role="dialog" aria-modal="true"', $html);
        $this->assertStringContainsString('Writer &lt;Toolkit&gt;', $html);
        $this->assertStringContainsString('49.00', $html);
        $this->assertStringContainsString('data-ppcart-notice="price_changed"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('name="ppcart_remove_from_cart" value="' . $id . ':standard:personal"', $html);
        $this->assertStringContainsString('Extension field', $html);
        $this->assertSame($cart, $seen);
        $this->assertStringNotContainsString($_COOKIE['ppcart_cart'], $html);
    }
}
