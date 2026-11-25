<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartCheckoutGateTest extends ShoppingCartTestCase
{
    private function paidOrder(string $email, array $meta): int
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_status' => 'paid']);
        foreach (['email' => $email, 'status' => 'paid'] + $meta as $key => $value) {
            update_post_meta($id, ppcart_meta_key($key), $value);
        }
        return $id;
    }

    private function item(int $orderId, int $productId, string $type, string $planId = 'personal'): void
    {
        $item = new \PPCart_Order_Item();
        foreach (['order_id' => $orderId, 'product_id' => $productId, 'price_id' => $planId, 'item_type' => $type, 'product_name' => 'Item', 'total_amount' => 29.0, 'quantity' => 1] as $key => $value) {
            $item->$key = $value;
        }
        $item->store();
    }

    private function limited(string $title, array $plans = []): int
    {
        $id = $this->product($title, $plans);
        update_post_meta($id, '_ppcart_customer_purchase_limit', '1');
        update_post_meta($id, '_ppcart_customer_limit', '1');
        update_post_meta($id, '_ppcart_customer_limit_message', 'Only one copy each.');
        return $id;
    }

    /** @test-id IT-450 */
    public function test_IT_450_a_line_the_customer_already_bought_is_refused_before_the_claim(): void
    {
        $limited = $this->limited('Limited Guide');
        $open = $this->product('Open Guide');
        $this->paidOrder('buyer@example.org', ['product_id' => $limited]);
        $this->add($open);
        $seen = $this->add($limited)->revision;

        $refused = $this->store()->claim_checkout($seen, ['email' => 'buyer@example.org']);
        $this->assertSame('ppcart_cart_purchase_limit', $refused->get_error_code());
        $this->assertSame('Only one copy each.', $refused->get_error_message());
        $this->assertSame($limited . ':personal', $refused->get_error_data()['key']);
        $this->assertSame($seen, $refused->get_error_data()['cart']->revision);
        $this->assertSame($seen, $this->store()->read()->revision);

        $accepted = $this->store()->claim_checkout($seen, ['email' => 'other@example.org']);
        $this->assertSame($seen + 1, $accepted->revision);
    }

    /** @test-id IT-450 */
    public function test_IT_450_a_cart_order_item_counts_toward_the_limit_and_a_bump_item_does_not(): void
    {
        $limited = $this->limited('Limited Guide');
        $other = $this->product('Other Guide');
        $customer = ['email' => 'buyer@example.org'];
        $this->assertTrue(ppcart_check_product_purchase_limit($limited, $customer));

        $bumpOrder = $this->paidOrder('buyer@example.org', ['product_id' => $other]);
        $this->item($bumpOrder, $other, 'main');
        $this->item($bumpOrder, $limited, 'bump');
        $this->assertTrue(ppcart_check_product_purchase_limit($limited, $customer));

        $cartOrder = $this->paidOrder('buyer@example.org', ['checkout_type' => 'cart']);
        $this->item($cartOrder, $other, 'main');
        $this->item($cartOrder, $limited, 'main');
        $this->assertSame('ppcart_purchase_limit_reached', ppcart_check_product_purchase_limit($limited, $customer)->get_error_code());
        $this->assertSame([$cartOrder], wp_list_pluck(ppcart_get_customer_product_orders($limited, $customer, 5), 'ID'));
        $this->assertTrue(ppcart_check_product_purchase_limit($limited, ['email' => 'other@example.org']));
        $this->assertFalse(ppcart_get_customer_product_orders($limited, ['email' => '']));

        update_post_meta($limited, '_ppcart_customer_limit', '2');
        $this->assertTrue(ppcart_check_product_purchase_limit($limited, $customer));
        $this->paidOrder('buyer@example.org', ['product_id' => $limited]);
        $this->assertWPError(ppcart_check_product_purchase_limit($limited, $customer));
    }

    /** @test-id IT-450 */
    public function test_IT_450_two_plans_of_one_product_in_a_cart_count_together_toward_the_limit(): void
    {
        $limited = $this->limited('Limited Guide', [$this->plan('personal', '29'), $this->plan('team', '59')]);
        $customer = ['email' => 'buyer@example.org'];
        $this->add($limited, 'personal');
        $seen = $this->add($limited, 'team')->revision;

        $refused = $this->store()->claim_checkout($seen, $customer);
        $this->assertWPError($refused);
        $this->assertSame('ppcart_cart_purchase_limit', $refused->get_error_code());
        $this->assertSame('You can buy Limited Guide 1 more time. Remove the other plans of this product from your cart.', $refused->get_error_message());
        $this->assertSame($limited . ':team', $refused->get_error_data()['key']);
        $this->assertSame($seen, $this->store()->read()->revision);

        update_post_meta($limited, '_ppcart_customer_limit', '2');
        $this->paidOrder('buyer@example.org', ['product_id' => $limited]);
        $this->assertSame($limited . ':team', $this->store()->claim_checkout($seen, $customer)->get_error_data()['key']);

        update_post_meta($limited, '_ppcart_customer_limit', '3');
        $this->assertSame($seen + 1, $this->store()->claim_checkout($seen, $customer)->revision);
    }

    /** @test-id IT-450 */
    public function test_IT_450_each_line_of_a_paid_cart_order_is_one_purchase(): void
    {
        $limited = $this->limited('Limited Guide', [$this->plan('personal', '29'), $this->plan('team', '59')]);
        update_post_meta($limited, '_ppcart_customer_limit', '2');
        $customer = ['email' => 'buyer@example.org'];
        $cartOrder = $this->paidOrder('buyer@example.org', ['checkout_type' => 'cart']);
        $this->item($cartOrder, $limited, 'main', 'personal');
        $this->item($cartOrder, $limited, 'main', 'team');

        $refused = ppcart_check_product_purchase_limit($limited, $customer);
        $this->assertWPError($refused);
        $this->assertSame('Only one copy each.', $refused->get_error_message());

        update_post_meta($limited, '_ppcart_customer_limit', '3');
        $this->assertTrue(ppcart_check_product_purchase_limit($limited, $customer));
    }

    /** @test-id IT-450 */
    public function test_IT_450_a_signed_in_customer_is_matched_by_account_with_another_email(): void
    {
        $limited = $this->limited('Limited Guide');
        $user = self::factory()->user->create(['user_email' => 'member@example.org']);
        $this->paidOrder('first@example.org', ['product_id' => $limited, 'user_account' => $user]);
        $this->assertTrue(ppcart_check_product_purchase_limit($limited, ['email' => 'second@example.org']));

        wp_set_current_user($user);
        $seen = $this->add($limited)->revision;
        $this->assertSame('ppcart_cart_purchase_limit', $this->store()->claim_checkout($seen, ['email' => 'second@example.org'])->get_error_code());
    }

    /** @test-id IT-450 */
    public function test_IT_450_stock_is_summed_over_the_lines_of_one_product(): void
    {
        $id = $this->product('Workshop Seat', [$this->plan('standard', '39'), $this->plan('team', '59')]);
        update_post_meta($id, '_ppcart_manage_stock', '1');
        update_post_meta($id, '_ppcart_limit', '1');
        $this->add($id, 'standard');
        $seen = $this->add($id, 'team')->revision;

        $refused = $this->store()->claim_checkout($seen, ['email' => 'buyer@example.org']);
        $this->assertSame('ppcart_cart_out_of_stock', $refused->get_error_code());
        $this->assertStringContainsString('Workshop Seat', $refused->get_error_message());
        $this->assertSame(2, $refused->get_error_data()['cart']->count());
        $this->assertSame($seen, $this->store()->read()->revision);

        update_post_meta($id, '_ppcart_limit', '2');
        $this->assertSame($seen + 1, $this->store()->claim_checkout($seen, ['email' => 'buyer@example.org'])->revision);
    }

    /** @test-id IT-450 */
    public function test_IT_450_the_sales_window_keeps_its_stock_rule(): void
    {
        $id = $this->product('Workshop Seat');
        $this->assertTrue(ppcart_check_product_stock($id, 50));
        $this->assertFalse(ppcart_is_cart_closed($id));

        update_post_meta($id, '_ppcart_manage_stock', '1');
        update_post_meta($id, '_ppcart_limit', '1');
        $this->assertTrue(ppcart_check_product_stock($id));
        $this->assertFalse(ppcart_check_product_stock($id, 2));
        $this->assertFalse(ppcart_is_cart_closed($id));

        update_post_meta($id, '_ppcart_limit', '0');
        $this->assertFalse(ppcart_check_product_stock($id));
        $this->assertTrue(ppcart_is_cart_closed($id));
    }
}
