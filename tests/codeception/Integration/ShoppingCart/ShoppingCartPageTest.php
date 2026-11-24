<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartPageTest extends ShoppingCartTestCase
{
    protected function tearDown(): void
    {
        foreach (['_ppcart_shop_page_id', '_ppcart_cart_page_id', '_ppcart_cart_checkout_page_id'] as $option) {
            delete_option($option);
        }
        remove_all_filters('ppcart_shopping_cart_totals');
        parent::tearDown();
    }

    /** @test-id IT-441 */
    public function test_IT_441_cart_block_shortcode_and_fragment_render_the_same_lines_and_server_totals(): void
    {
        $one = $this->product('Writer Toolkit', [$this->plan('standard:personal', '39')]);
        $two = $this->product('Video Course', [$this->plan('full', '149')]);
        $this->add($one, 'standard:personal');
        $this->add($two, 'full');
        $html = ppcart_render_shopping_cart();
        $this->assertStringContainsString('Writer Toolkit', $html);
        $this->assertStringContainsString('Video Course', $html);
        $this->assertStringContainsString('standard:personal', $html);
        $this->assertStringContainsString('188.00', $html);
        $this->assertStringContainsString('data-ppcart-total="subtotal"', $html);
        $this->assertStringContainsString('data-ppcart-total="total"', $html);
        $this->assertStringContainsString('Continue shopping', $html);
        $this->assertSame($html, do_shortcode('[ppcart_cart]'));
        $this->assertSame($html, render_block(['blockName' => 'publishpress-cart/cart', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []]));
        $request = new \WP_REST_Request('GET', '/ppcart/v1/cart');
        $request->set_param('fragments', 'drawer,cart');
        $response = (new \PPCart_Shopping_Cart_View())->response(ppcart_get_shopping_cart(), $request);
        $this->assertSame($html, $response['fragments']['cart']);
        $this->assertStringContainsString('188.00', $response['fragments']['drawer']);
        $this->assertTrue(DONOTCACHEPAGE);
    }

    /** @test-id IT-441 */
    public function test_IT_441_cart_notices_survive_a_second_render_and_removing_the_last_line_shows_the_empty_state(): void
    {
        $shop = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        update_option('_ppcart_shop_page_id', $shop);
        $id = $this->product('Writer Toolkit', [$this->plan('personal', '19')]);
        $this->add($id);
        update_post_meta($id, '_ppcart_pay_options', [$this->plan('personal', '29')]);
        $changed = ppcart_render_shopping_cart();
        $this->assertStringContainsString('data-ppcart-notice="price_changed"', $changed);
        $this->assertStringContainsString('changed from', $changed);
        $this->assertSame($changed, ppcart_render_shopping_cart());
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $changed);
        $xpath = new \DOMXPath($document);
        $form = $xpath->query('//form[contains(@class,"ppcart-shopping-cart-remove")]')->item(0);
        $this->assertSame('post', $form->getAttribute('method'));
        $post = [];
        foreach ($xpath->query('.//input', $form) as $input) {
            $post[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $this->assertSame($id . ':personal', $post['ppcart_remove_from_cart']);
        $this->assertSame(1, wp_verify_nonce($post['ppcart_shopping_cart_nonce'], 'ppcart_remove_from_cart'));
        $this->assertIsString((new \PPCart_Public_Shopping_Cart_Controller())->handle_post($post));
        $empty = ppcart_render_shopping_cart();
        $this->assertStringContainsString('Your cart is empty.', $empty);
        $this->assertStringContainsString('Browse the shop', $empty);
        $this->assertStringContainsString('href="' . esc_url(get_permalink($shop)) . '"', $empty);
        $this->assertStringNotContainsString('ppcart-shopping-cart-remove', $empty);
        $this->assertStringNotContainsString('price_changed', $empty);
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);
    }

    /** @test-id IT-441 */
    public function test_IT_441_rendering_removes_an_ineligible_line_and_shows_its_notice(): void
    {
        $id = $this->product('Writer Toolkit');
        $this->add($id);
        update_post_meta($id, '_ppcart_show_in_shop', 'no');
        $html = ppcart_render_shopping_cart();
        $this->assertStringContainsString('data-ppcart-notice="removed"', $html);
        $this->assertStringContainsString('Writer Toolkit was removed', $html);
        $this->assertStringContainsString('Your cart is empty.', $html);
        $this->assertStringNotContainsString('ppcart-shopping-cart-remove', $html);
    }

    /** @test-id IT-441 */
    public function test_IT_441_style_tokens_and_extension_total_rows_reach_the_cart(): void
    {
        $this->add($this->product('Writer Toolkit'));
        add_filter('ppcart_shopping_cart_totals', static function (array $totals): array {
            array_splice($totals['rows'], 1, 0, [['type' => 'discount', 'label' => 'Member discount', 'amount' => -5]]);
            $totals['rows'][2]['amount'] = 24;
            $totals['total'] = 24;
            return $totals;
        });
        $html = ppcart_render_shopping_cart(['styleSettings' => ['preset' => 'carded', 'accentColor' => '#2271b1']]);
        $this->assertStringContainsString('--ppcart-accent:#2271b1;', $html);
        $this->assertStringContainsString('--ppcart-radius:10px;', $html);
        $this->assertStringContainsString('data-ppcart-total="discount"', $html);
        $this->assertStringContainsString('Member discount', $html);
        $this->assertStringContainsString('24.00', $html);
        $this->assertSame(29.0, ppcart_get_shopping_cart()->subtotal());
    }
}
