<?php

declare(strict_types=1);

namespace Tests\Support\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

abstract class ShoppingCartTestCase extends WPTestCase
{
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        unset($_COOKIE['ppcart_cart']);
        wp_set_current_user(0);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['ppcart_cart']);
        wp_set_current_user(0);
        parent::tearDown();
    }

    protected function product(string $title, array $plans = []): int
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish', 'post_title' => $title]);
        update_post_meta($id, '_ppcart_pay_options', $plans ?: [$this->plan('personal', '29')]);
        return $id;
    }

    protected function plan(string $id, string $price, string $type = '', string $stripeId = ''): array
    {
        return ['option_id' => $id, 'option_name' => ucfirst($id), 'price' => $price, 'product_type' => $type, 'stripe_plan_id' => $stripeId, 'sale_stripe_plan_id' => '', 'interval' => 'month', 'frequency' => '1', 'installments' => '-1'];
    }

    protected function store(): \PPCart_Shopping_Cart_Store
    {
        return new \PPCart_Shopping_Cart_Store();
    }

    /** @return \PPCart_Shopping_Cart_Priced|\WP_Error */
    protected function add(int $productId, string $planId = 'personal')
    {
        return $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->add_item($productId, $planId), true);
    }

    protected function rowByKey(string $cartKey): ?object
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM %i WHERE cart_key = %s', \PPCart_Shopping_Cart_Store::table(), $cartKey));
    }

    protected function rowCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i', \PPCart_Shopping_Cart_Store::table()));
    }

    /** @return list<string> */
    protected function keys(\PPCart_Shopping_Cart_Priced $cart): array
    {
        return array_map(static fn (\PPCart_Shopping_Cart_Priced_Line $line): string => $line->key, $cart->lines);
    }

    protected function asGuestWith(string $cartKey = ''): void
    {
        wp_set_current_user(0);
        unset($_COOKIE['ppcart_cart']);
        if ('' !== $cartKey) {
            $_COOKIE['ppcart_cart'] = $cartKey;
        }
    }
}
