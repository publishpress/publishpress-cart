<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use lucatume\WPBrowser\TestCase\WPTestCase;

class ShopTest extends WPTestCase
{
    protected $tester;

    protected function tearDown(): void
    {
        $_POST = [];
        wp_set_current_user(0);
        remove_filter('ppcart_process_stripe_products', '__return_false');
        parent::tearDown();
    }

    private function product(string $title, array $plans = []): int
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish', 'post_title' => $title]);
        update_post_meta($id, '_ppcart_pay_options', $plans ?: [$this->plan('personal', '29')]);
        return $id;
    }

    private function plan(string $id, string $price, string $type = ''): array
    {
        return ['option_id' => $id, 'option_name' => $id, 'price' => $price, 'product_type' => $type, 'stripe_plan_id' => '', 'sale_stripe_plan_id' => '', 'interval' => 'month', 'frequency' => '1', 'installments' => '-1'];
    }

    /** @test-id IT-435 */
    public function test_IT_435_unchecked_visibility_stays_off_and_buy_now_still_renders(): void
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        add_filter('ppcart_process_stripe_products', '__return_false');
        $id = $this->product('Hidden Shop Product');
        $this->assertTrue(ppcart_product_shows_in_shop($id));
        $_POST = ['ppcart_fields_nonce' => wp_create_nonce('ppcart'), '_ppcart_shop_general_submitted' => '1'];
        $metabox = new \PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
        $metabox->validate_meta($id, get_post($id));
        $this->assertSame('no', get_post_meta($id, '_ppcart_show_in_shop', true));
        $this->assertStringNotContainsString('Hidden Shop Product', ppcart_render_shop());
        $this->assertFalse((new \PPCart_Shopping_Cart_Catalog())->product_is_eligible($id));
        update_post_meta($id, '_ppcart_button_color', '#000000');
        update_post_meta($id, '_ppcart_pay_options', [$this->plan('personal', '29')]);
        $buyNow = do_shortcode('[ppcart_form id="' . $id . '"]');
        $this->assertStringContainsString('ppcart-payment-form', $buyNow);
        $_POST['_ppcart_show_in_shop'] = '1';
        $metabox->validate_meta($id, get_post($id));
        $this->assertSame('yes', get_post_meta($id, '_ppcart_show_in_shop', true));
        $this->assertStringContainsString('Hidden Shop Product', ppcart_render_shop());
        unset($_POST['_ppcart_shop_general_submitted'], $_POST['_ppcart_show_in_shop']);
        $metabox->validate_meta($id, get_post($id));
        $this->assertSame('yes', get_post_meta($id, '_ppcart_show_in_shop', true));
    }

    /** @test-id IT-435 */
    public function test_IT_435_cards_offer_only_eligible_plans_and_keep_text_plan_ids(): void
    {
        $id = $this->product('Two plans', [$this->plan('standard:personal', '39'), $this->plan('team', '59'), $this->plan('monthly', '19', 'recurring')]);
        $catalog = new \PPCart_Shopping_Cart_Catalog();
        $card = $catalog->shop_card($id);
        $this->assertSame('add', $card['action']);
        $this->assertSame('standard:personal', $card['selected']);
        $this->assertSame(['standard:personal', 'team'], array_column($card['plans'], 'plan_id'));
        $this->assertSame(39.0, $catalog->resolve_line($id, 'standard:personal')['unit_price']);
        $this->assertSame('ppcart_cart_plan_not_eligible', $catalog->resolve_line($id, 'monthly')->get_error_code());
        $this->assertSame('ppcart_cart_plan_not_eligible', $catalog->resolve_line($id, 'unknown')->get_error_code());
        $html = ppcart_render_shop();
        $this->assertStringContainsString('name="plan_id"', $html);
        $this->assertStringContainsString('value="standard:personal"', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $subscription = $this->product('Subscription', [$this->plan('monthly', '19', 'recurring')]);
        $this->assertSame('subscribe', $catalog->shop_card($subscription)['action']);
        $this->assertStringContainsString('/ month', $catalog->shop_card($subscription)['price_label']);
        update_option('_ppcart_shopping_cart_enable', '0');
        $this->assertSame('view', $catalog->shop_card($id)['action']);
        delete_option('_ppcart_shopping_cart_enable');
    }

    /** @test-id IT-435 */
    public function test_IT_435_hard_visibility_and_status_limits_survive_query_filter(): void
    {
        $visible = $this->product('Visible');
        $hidden = $this->product('Hidden');
        update_post_meta($hidden, '_ppcart_show_in_shop', 'no');
        $draft = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'draft']);
        $filter = static function ($args) {
            $args['post_status'] = 'any';
            $args['post_type'] = 'post';
            $args['meta_query'] = [];
            return $args;
        };
        add_filter('ppcart_shop_query_args', $filter);
        try {
            $shop = new \PPCart_Shop();
            $ids = wp_list_pluck($shop->query($shop->normalize_attributes([]))->posts, 'ID');
            $this->assertContains($visible, $ids);
            $this->assertNotContains($hidden, $ids);
            $this->assertNotContains($draft, $ids);
        } finally {
            remove_filter('ppcart_shop_query_args', $filter);
        }
        $this->assertSame('[ppcart_store]', do_shortcode('[ppcart_store]'));
        $this->assertStringContainsString('Visible', do_blocks('<!-- wp:publishpress-cart/shop /-->'));
        $this->assertStringContainsString('Visible', do_shortcode('[ppcart_shop]'));
    }

    /** @test-id IT-435 */
    public function test_IT_435_eligibility_enforces_stock_sales_window_aliases_and_free_prices(): void
    {
        $id = $this->product('Eligible');
        $catalog = new \PPCart_Shopping_Cart_Catalog();
        $this->assertTrue($catalog->product_is_eligible($id));
        update_post_meta($id, '_ppcart_manage_stock', '1');
        update_post_meta($id, '_ppcart_limit', '0');
        $this->assertFalse($catalog->product_is_eligible($id));
        delete_post_meta($id, '_ppcart_manage_stock');
        update_post_meta($id, '_ppcart_checkout_starts', '2099-01-01');
        $this->assertFalse($catalog->product_is_eligible($id));
        delete_post_meta($id, '_ppcart_checkout_starts');
        $plan = $this->plan('free:guide', '0', 'free');
        $plan['url_slug'] = 'guide';
        update_post_meta($id, '_ppcart_pay_options', [$plan]);
        $this->assertSame(0.0, $catalog->resolve_line($id, 'free:guide')['unit_price']);
        $this->assertSame('Free', $catalog->shop_card($id)['price_label']);
        $this->assertSame('ppcart_cart_plan_not_eligible', $catalog->resolve_line($id, 'guide')->get_error_code());
    }

    /** @test-id IT-435 */
    public function test_IT_435_price_order_uses_current_offer_and_paginates(): void
    {
        $high = $this->product('High', [$this->plan('one', '59')]);
        $low = $this->product('Low', [$this->plan('one', '9')]);
        $shop = new \PPCart_Shop();
        $query = $shop->query($shop->normalize_attributes(['orderBy' => 'price', 'perPage' => 1]));
        $this->assertSame([$low], wp_list_pluck($query->posts, 'ID'));
        $this->assertSame(2, $query->max_num_pages);
        set_query_var('paged', 2);
        $query = $shop->query($shop->normalize_attributes(['orderBy' => 'price', 'perPage' => 1]));
        $this->assertSame([$high], wp_list_pluck($query->posts, 'ID'));
        set_query_var('paged', 0);
    }

    /** @test-id IT-435 */
    public function test_IT_435_hiding_subscriptions_keeps_mixed_products_and_paginates_what_remains(): void
    {
        $oneTime = $this->product('One Time', [$this->plan('one', '29')]);
        $mixed = $this->product('Mixed', [$this->plan('one', '49'), $this->plan('monthly', '19', 'recurring')]);
        $this->product('Monthly Only', [$this->plan('monthly', '19', 'recurring')]);
        $shop = new \PPCart_Shop();

        $all = $shop->query($shop->normalize_attributes(['perPage' => 10]));
        $this->assertCount(3, $all->posts);
        $this->assertStringContainsString('Monthly Only', ppcart_render_shop());

        $query = $shop->query($shop->normalize_attributes(['perPage' => 1, 'orderBy' => 'title', 'includeSubscriptions' => false]));
        $this->assertSame([$mixed], wp_list_pluck($query->posts, 'ID'));
        $this->assertSame(2, $query->max_num_pages);
        set_query_var('paged', 2);
        $query = $shop->query($shop->normalize_attributes(['perPage' => 1, 'orderBy' => 'title', 'includeSubscriptions' => false]));
        $this->assertSame([$oneTime], wp_list_pluck($query->posts, 'ID'));
        set_query_var('paged', 0);

        $html = do_shortcode('[ppcart_shop includesubscriptions="false"]');
        $this->assertStringContainsString('One Time', $html);
        $this->assertStringContainsString('Mixed', $html);
        $this->assertStringNotContainsString('Monthly Only', $html);
    }

    /** @test-id IT-435 */
    public function test_IT_435_shop_style_ignores_saved_surface_density_and_corners(): void
    {
        $this->product('Styled');
        $html = ppcart_render_shop(['styleSettings' => ['surfaceStyle' => 'boxed', 'density' => 'spacious', 'cornerRadius' => 'rounded']]);
        $this->assertStringNotContainsString('--ppcart-surface', $html);
        $this->assertStringNotContainsString('--ppcart-radius', $html);
        $html = ppcart_render_shop(['styleSettings' => ['accentColor' => '#123456', 'cornerRadius' => 'rounded']]);
        $this->assertStringContainsString('--ppcart-accent:#123456', $html);
        $this->assertStringNotContainsString('--ppcart-radius', $html);
        $this->assertStringContainsString('--ppcart-radius', ppcart_render_shop(['styleSettings' => ['preset' => 'compact']]));
    }

    /** @test-id IT-435 */
    public function test_IT_435_shared_styles_keep_base_and_preset_values(): void
    {
        $this->assertSame([], ppcart_block_style_tokens([]));
        $this->assertSame([], ppcart_block_style_tokens(['accentColor' => 'url(evil)', 'density' => 'invalid']));
        $tokens = ppcart_block_style_tokens(['preset' => 'minimal']);
        $this->assertSame('#2271b1', $tokens['--ppcart-accent']);
        $this->assertSame('#1e639c', $tokens['--ppcart-accent-hover']);
        $this->assertSame('38px', $tokens['--ppcart-field-height']);
        $this->assertSame('0', $tokens['--ppcart-radius']);
        $this->assertArrayNotHasKey('--ppcart-accent', ppcart_block_style_tokens(['density' => 'compact']));
    }
}
