<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use lucatume\WPBrowser\TestCase\WPTestCase;

class SettingsTest extends WPTestCase
{
    protected $tester;

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        $_REQUEST = [];
        parent::tearDown();
    }

    private function settings(): \PPCart_Shopping_Cart_Settings
    {
        return new \PPCart_Shopping_Cart_Settings();
    }

    private function registerFields(): void
    {
        (new \PPCart_Admin_Settings('ppcart', 'PublishPress Cart', '1.0'))->register_fields();
    }

    /** @test-id IT-436 */
    public function test_IT_436_defaults_and_native_settings_save_every_cart_field(): void
    {
        $settings = $this->settings();
        $this->assertTrue($settings->enabled());
        $this->assertSame('mini_cart', $settings->after_add());
        $this->assertSame(7, $settings->lifetime_days());
        $this->assertSame(['shop' => '', 'cart' => '', 'checkout' => ''], $settings->urls());
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $this->registerFields();
        $values = [
            '_ppcart_shopping_cart_enable' => 0,
            '_ppcart_shop_page_id' => $page,
            '_ppcart_cart_page_id' => $page,
            '_ppcart_cart_checkout_page_id' => $page,
            '_ppcart_after_add_to_cart' => 'checkout',
            '_ppcart_cart_checkout_phone' => 1,
            '_ppcart_cart_checkout_address' => 1,
            '_ppcart_cart_checkout_terms' => 1,
            '_ppcart_cart_checkout_terms_url' => 'https://example.org/terms',
            '_ppcart_cart_checkout_button_text' => 'Purchase {total}',
            '_ppcart_cart_confirmation' => 'url',
            '_ppcart_cart_confirmation_page' => $page,
            '_ppcart_cart_redirect' => 'https://example.org/thanks',
            '_ppcart_cart_confirmation_message' => '<p>Thank you.</p>',
            '_ppcart_shopping_cart_lifetime' => 14,
        ];
        foreach ($values as $id => $value) {
            update_option($id, $value);
            $this->assertEquals($value, get_option($id), $id);
        }
        $this->assertFalse($settings->enabled());
        $this->assertSame('checkout', $settings->after_add());
        $this->assertSame(14, $settings->lifetime_days());
        $this->assertSame('Purchase {total}', $settings->button_text());
        $this->assertSame(get_permalink($page), $settings->urls()['shop']);
        $this->assertFalse(get_option('_ppcart_shopping_cart_create_pages'));
    }

    /** @test-id IT-436 */
    public function test_IT_436_native_sanitizers_reject_invalid_enum_page_and_lifetime_values(): void
    {
        $this->registerFields();
        update_option('_ppcart_after_add_to_cart', 'external');
        update_option('_ppcart_cart_confirmation', 'evil');
        update_option('_ppcart_shop_page_id', self::factory()->post->create(['post_type' => 'post']));
        update_option('_ppcart_shopping_cart_lifetime', -3);
        $this->assertSame('mini_cart', $this->settings()->after_add());
        $this->assertSame('message', get_option('_ppcart_cart_confirmation'));
        $this->assertEquals(0, get_option('_ppcart_shop_page_id'));
        $this->assertSame(1, $this->settings()->lifetime_days());
        update_option('_ppcart_shopping_cart_lifetime', ['evil']);
        $this->assertSame(7, $this->settings()->lifetime_days());
        update_option('_ppcart_cart_redirect', 'javascript:alert(1)');
        $this->assertSame('', get_option('_ppcart_cart_redirect'));
        update_option('_ppcart_cart_confirmation_message', '<script>alert(1)</script><p>Thanks</p>');
        $this->assertStringNotContainsString('<script>', get_option('_ppcart_cart_confirmation_message'));
    }

    /** @test-id IT-436 */
    public function test_IT_436_checkout_field_list_controls_optional_fields_and_extension_fields(): void
    {
        $this->assertSame(['first_name', 'last_name', 'email'], array_keys($this->settings()->checkout_fields()));
        update_option('_ppcart_cart_checkout_phone', 1);
        update_option('_ppcart_cart_checkout_address', 1);
        update_option('_ppcart_cart_checkout_terms', 1);
        $fields = $this->settings()->checkout_fields();
        $this->assertArrayHasKey('phone', $fields);
        $this->assertFalse($fields['phone']['required']);
        $this->assertTrue($fields['address1']['required']);
        $this->assertFalse($fields['address2']['required']);
        $this->assertArrayNotHasKey('terms', $fields);
        update_option('_ppcart_cart_checkout_terms_url', 'https://example.org/terms');
        $this->assertTrue($this->settings()->checkout_fields()['terms']['required']);
        $filter = static function ($fields) {
            $fields['company'] = ['type' => 'text', 'label' => 'Company', 'required' => true];
            return $fields;
        };
        add_filter('ppcart_shopping_cart_checkout_fields', $filter);
        try {
            $this->assertTrue($this->settings()->checkout_fields()['company']['required']);
        } finally {
            remove_filter('ppcart_shopping_cart_checkout_fields', $filter);
        }
    }

    /** @test-id IT-436 */
    public function test_IT_436_page_creation_fills_only_missing_pages_and_preserves_merchant_pages(): void
    {
        $settings = $this->settings();
        $first = $settings->create_missing_pages();
        $this->assertCount(3, $first['created']);
        $shop = $first['created']['shop'];
        $cart = $first['created']['cart'];
        $checkout = $first['created']['checkout'];
        $this->assertSame('publishpress-cart/shop', parse_blocks(get_post_field('post_content', $shop))[0]['blockName']);
        $this->assertSame('publishpress-cart/cart', parse_blocks(get_post_field('post_content', $cart))[0]['blockName']);
        $this->assertSame(['pid' => 'cart'], parse_blocks(get_post_field('post_content', $checkout))[0]['attrs']);
        $again = $settings->create_missing_pages();
        $this->assertSame([], $again['created']);
        $this->assertSame(['shop' => $shop, 'cart' => $cart, 'checkout' => $checkout], $again['skipped']);
        wp_trash_post($cart);
        $replaced = $settings->create_missing_pages();
        $this->assertSame(['cart'], array_keys($replaced['created']));
        $this->assertNotSame($cart, $replaced['created']['cart']);
        $merchant = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish', 'post_content' => 'My shop']);
        update_option('_ppcart_shop_page_id', $merchant);
        $this->assertSame($merchant, $settings->create_missing_pages()['skipped']['shop']);
        $this->assertSame('My shop', get_post_field('post_content', $merchant));
    }

    /** @test-id IT-436 */
    public function test_IT_436_page_action_requires_post_capability_and_nonce(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $die = static fn () => static function ($message) {
            throw new \RuntimeException(wp_strip_all_tags((string) $message));
        };
        add_filter('wp_die_handler', $die);
        try {
            foreach ([['GET', 'administrator', true], ['POST', 'subscriber', true], ['POST', 'administrator', false]] as [$requestMethod, $role, $validNonce]) {
                wp_set_current_user(self::factory()->user->create(['role' => $role]));
                $_SERVER['REQUEST_METHOD'] = $requestMethod;
                $_REQUEST = ['_ppcart_shopping_cart_pages_nonce' => $validNonce ? wp_create_nonce('ppcart_shopping_cart_create_pages') : 'invalid'];
                $refused = false;
                try {
                    (new \PPCart_Admin_Shopping_Cart_Pages($this->settings()))->create_missing_pages();
                } catch (\RuntimeException $error) {
                    $refused = true;
                }
                $this->assertTrue($refused);
                $this->assertFalse(get_option('_ppcart_shop_page_id'));
            }
        } finally {
            remove_filter('wp_die_handler', $die);
            if (null === $method) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $method;
            }
        }
    }

    /** @test-id IT-436 */
    public function test_IT_436_confirmation_uses_cart_settings_and_signed_order_links(): void
    {
        $checkout = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $thanks = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $orderId = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        update_post_meta($orderId, '_ppcart_firstname', 'Ada');
        update_option('_ppcart_cart_checkout_page_id', $checkout);
        update_option('_ppcart_cart_confirmation_message', '<p>Thanks {customer_firstname}</p>');
        $order = new \PPCart_Order($orderId);
        $confirmation = $this->settings()->confirmation($order);
        $this->assertSame('message', $confirmation['type']);
        $this->assertStringContainsString('Thanks Ada', $confirmation['message']);
        $this->assertStringContainsString('ppcart-order=' . $orderId, $confirmation['url']);
        $this->assertStringContainsString('token=', $confirmation['url']);
        update_option('_ppcart_cart_confirmation', 'page');
        update_option('_ppcart_cart_confirmation_page', $thanks);
        $this->assertStringStartsWith(get_permalink($thanks), $this->settings()->confirmation($order)['url']);
        update_option('_ppcart_cart_confirmation', 'url');
        update_option('_ppcart_cart_redirect', 'https://example.org/receipt?from=cart');
        $this->assertSame('https://example.org/receipt?from=cart', $this->settings()->confirmation($order)['url']);
    }
}
