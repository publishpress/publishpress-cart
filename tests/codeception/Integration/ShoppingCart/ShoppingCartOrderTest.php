<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartOrderTest extends ShoppingCartTestCase
{
    private const CUSTOMER = [
        'email'      => 'Buyer@Example.org',
        'first_name' => 'Ada',
        'last_name'  => 'Lovelace',
        'phone'      => '555 0100',
        'pay-method' => 'stripe',
        'address1'   => '1 Analytical Way',
        'city'       => 'London',
        'country'    => 'GB',
    ];

    protected function tearDown(): void
    {
        remove_all_filters('ppcart_shopping_cart_order_loaded');
        remove_all_filters('ppcart_charge_amount');
        parent::tearDown();
    }

    /** @return array{0:int,1:int,2:\PPCart_Shopping_Cart_Priced} */
    private function twoLineCart(): array
    {
        $toolkit = $this->product('Writer Toolkit');
        $course = $this->product('Editing Course', [$this->plan('solo', '39'), $this->plan('team', '59')]);
        $this->add($toolkit);
        return [$toolkit, $course, $this->add($course, 'team')];
    }

    private function order(\PPCart_Shopping_Cart_Priced $cart): \PPCart_Order
    {
        $order = new \PPCart_Order();
        $order->load_from_shopping_cart($cart, self::CUSTOMER);
        return $order;
    }

    /** @test-id IT-443 */
    public function test_IT_443_order_takes_items_amounts_and_customer_from_the_priced_cart(): void
    {
        [$toolkit, $course, $cart] = $this->twoLineCart();
        $order = $this->order($cart);

        $this->assertSame('cart', $order->checkout_type);
        $this->assertSame(0, $order->product_id);
        $this->assertSame(['cart', 'cart', 1], [$order->option_id, $order->plan_id, $order->quantity]);
        $this->assertSame('Writer Toolkit + 1 more', $order->product_name);
        $this->assertSame('Writer Toolkit + 1 more', $order->item_name);
        $this->assertSame(['one-time', 'cart', 88.0, 88.0], [$order->plan->type, $order->plan->option_id, $order->plan->price, $order->plan->initial_payment]);
        $this->assertSame([88.0, 88.0, 88.0, 88.0], [(float) $order->amount, (float) $order->invoice_total, (float) $order->invoice_subtotal, (float) $order->pre_tax_amount]);
        $this->assertSame($cart->cart_id, $order->shopping_cart_id);
        $this->assertSame($cart->paid_lines(), $order->shopping_cart_lines);
        $this->assertSame(['buyer@example.org', 'Ada', 'Lovelace', 'Ada Lovelace', '555 0100', 'stripe', 'London', 'GB'], [$order->email, $order->first_name, $order->last_name, $order->customer_name, $order->phone, $order->pay_method, $order->city, $order->country]);
        $this->assertNull($order->order_bumps);
        $this->assertNull($order->coupon);
        $this->assertNull($order->custom_fields);
        $this->assertSame(
            [
                ['main', $toolkit, 'personal', 'Writer Toolkit', 'Personal', 29.0, 1, 29.0, 29.0],
                ['main', $course, 'team', 'Editing Course', 'Team', 59.0, 1, 59.0, 59.0],
            ],
            array_map(static fn (array $item): array => [$item['item_type'], $item['product_id'], $item['price_id'], $item['product_name'], $item['price_name'], $item['unit_price'], $item['quantity'], $item['subtotal'], $item['total_amount']], $order->items)
        );

        $single = new \PPCart_Order();
        $this->asGuestWith();
        $single->load_from_shopping_cart($this->add($toolkit), self::CUSTOMER);
        $this->assertSame('Writer Toolkit', $single->product_name);
    }

    /** @test-id IT-443 */
    public function test_IT_443_stored_order_reloads_as_a_cart_order_with_its_items_and_plan(): void
    {
        [$toolkit, $course, $cart] = $this->twoLineCart();
        $id = $this->order($cart)->store();

        $this->assertSame('cart', get_post_meta($id, '_ppcart_checkout_type', true));
        $this->assertFalse(metadata_exists('post', $id, '_ppcart_product_id'));

        $reloaded = new \PPCart_Order($id);
        $this->assertTrue(ppcart_order_is_shopping_cart($reloaded));
        $this->assertNull($reloaded->product_id);
        $this->assertSame([88.0, 'pending-payment'], [(float) $reloaded->amount, $reloaded->status]);
        $this->assertSame($cart->cart_id, (int) $reloaded->shopping_cart_id);
        $this->assertSame($cart->paid_lines(), $reloaded->shopping_cart_lines);
        $this->assertSame(
            [['main', $toolkit, 'personal', 29.0, 1], ['main', $course, 'team', 59.0, 1]],
            array_map(static fn (\PPCart_Order_Item $item): array => [$item->item_type, (int) $item->product_id, $item->price_id, (float) $item->total_amount, (int) $item->quantity], $reloaded->get_items())
        );

        foreach ([ppcart_setup_order($id), (object) ppcart_setup_order($id, true)] as $setup) {
            $this->assertTrue(ppcart_order_is_shopping_cart($setup));
            $this->assertSame('', $setup->product_id);
            $this->assertSame(['one-time', 'cart', 'Writer Toolkit + 1 more', 88.0], [$setup->plan->type, $setup->plan->option_id, $setup->plan->name, $setup->plan->price]);
            $this->assertSame(88.0, $setup->amount);
        }

        $buyNow = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_status' => 'paid']);
        $this->assertFalse(ppcart_order_is_shopping_cart(new \PPCart_Order($buyNow)));
        $this->assertFalse(ppcart_order_is_shopping_cart((array) ppcart_setup_order($buyNow, true)));
        $this->assertNull(ppcart_shopping_cart_order_plan((array) ppcart_setup_order($buyNow, true)));
    }

    /** @test-id IT-443 */
    public function test_IT_443_each_main_item_gives_one_context_with_the_parent_order_id(): void
    {
        $free = $this->product('Free Sample', [$this->plan('gift', '0', 'free')]);
        [$toolkit, $course] = $this->twoLineCart();
        $id = $this->order($this->add($free, 'gift'))->store();
        $data = (new \PPCart_Order($id))->get_data();
        $contexts = ppcart_shopping_cart_order_contexts($data);

        $this->assertSame(
            [[$toolkit, 'personal', 'personal', 29.0, 1, 'Writer Toolkit', 'Personal'], [$course, 'team', 'team', 59.0, 1, 'Editing Course', 'Team'], [$free, 'gift', 'gift', 0.0, 1, 'Free Sample', 'Gift']],
            array_map(static fn (array $context): array => [$context['product_id'], $context['option_id'], $context['plan_id'], $context['amount'], $context['quantity'], $context['product_name'], $context['item_name']], $contexts)
        );
        foreach ($contexts as $context) {
            $this->assertSame([$id, $id, 'main', 'cart', 'buyer@example.org'], [$context['ID'], $context['id'], $context['order_type'], $context['checkout_type'], $context['email']]);
            $this->assertGreaterThan(0, $context['shopping_cart_item_id']);
        }
        $this->assertCount(3, array_unique(array_column($contexts, 'shopping_cart_item_id')));
        $this->assertSame([], ppcart_shopping_cart_order_contexts(['ID' => 0]));
    }

    /** @test-id IT-443 */
    public function test_IT_443_extension_filter_edits_items_before_the_totals_are_computed(): void
    {
        [, , $cart] = $this->twoLineCart();
        $chargedFor = 'unset';
        add_filter('ppcart_charge_amount', static function ($amount, $product) use (&$chargedFor) {
            $chargedFor = $product;
            return $amount;
        }, 10, 2);
        add_filter('ppcart_shopping_cart_order_loaded', static function (\PPCart_Order $order, \PPCart_Shopping_Cart_Priced $priced): \PPCart_Order {
            $order->items[0]['subtotal'] = 19.0;
            $order->items[0]['total_amount'] = 19.0;
            return $order;
        }, 10, 2);
        $order = $this->order($cart);
        $this->assertSame([78.0, 78.0, 78.0], [(float) $order->amount, (float) $order->pre_tax_amount, $order->plan->price]);
        $this->assertNull($chargedFor);

        remove_all_filters('ppcart_shopping_cart_order_loaded');
        add_filter('ppcart_shopping_cart_order_loaded', static fn (): \PPCart_Order => new \PPCart_Order());
        $this->setExpectedIncorrectUsage('PPCart_Order::load_from_shopping_cart');
        $this->assertSame(88.0, (float) $this->order($cart)->amount);
    }

    /** @test-id IT-443 */
    public function test_IT_443_cart_order_without_item_rows_shows_no_item_for_product_zero(): void
    {
        [, , $cart] = $this->twoLineCart();
        $order = $this->order($cart);
        $order->items = [];
        $id = $order->store();
        $this->assertSame([], ppcart_get_order_items($id));
    }
}
