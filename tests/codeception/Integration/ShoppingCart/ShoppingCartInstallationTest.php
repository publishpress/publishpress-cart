<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

class ShoppingCartInstallationTest extends \Tests\NoTransactionWPTestCase
{
    /** @test-id IT-459 */
    public function test_IT_459_a_normal_request_recreates_a_missing_cart_table_before_the_next_write(): void
    {
        global $wpdb;
        wp_set_current_user(0);
        unset($_COOKIE['ppcart_cart']);
        $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', \PPCart_Shopping_Cart_Store::table()));
        $id = 0;
        try {
            $this->assertNull($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like(\PPCart_Shopping_Cart_Store::table()))));
            new \PPCart_Order_Items();
            $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
            update_post_meta($id, '_ppcart_pay_options', [['option_id' => 'personal', 'option_name' => 'Personal', 'price' => '29', 'product_type' => '']]);
            $cart = (new \PPCart_Shopping_Cart_Store())->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->add_item($id, 'personal'), true);
            $this->assertNotWPError($cart);
            $this->assertSame($id, $cart->lines[0]->product_id);
            $this->assertSame(1, $cart->count());
            $this->assertSame((string) $cart->cart_id, $wpdb->get_var($wpdb->prepare('SELECT id FROM %i WHERE cart_key = %s', \PPCart_Shopping_Cart_Store::table(), $_COOKIE['ppcart_cart'])));
        } finally {
            \PPCart_Shopping_Cart_Store::install();
            $wpdb->query($wpdb->prepare('DELETE FROM %i', \PPCart_Shopping_Cart_Store::table()));
            if ($id) {
                wp_delete_post($id, true);
            }
            unset($_COOKIE['ppcart_cart']);
        }
    }
}
