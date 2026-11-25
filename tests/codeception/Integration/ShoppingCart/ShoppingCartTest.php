<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartTest extends ShoppingCartTestCase
{
    protected function tearDown(): void
    {
        delete_option('_ppcart_shopping_cart_enable');
        remove_all_filters('ppcart_shopping_cart_max_items');
        remove_all_filters('ppcart_shopping_cart_item_max_quantity');
        remove_all_filters('ppcart_plan_data');
        parent::tearDown();
    }

    /** @test-id IT-445 */
    public function test_IT_445_table_is_installed_by_the_upgrade_routine_with_a_revision_column(): void
    {
        global $wpdb;
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-upgrade.php';
        wp_clear_scheduled_hook('ppcart_shopping_cart_cleanup');
        \PPCart_Upgrade::setup_shopping_cart_table();
        \PPCart_Upgrade::setup_shopping_cart_table();
        $this->assertSame($wpdb->prefix . 'ppcart_shopping_carts', \PPCart_Shopping_Cart_Store::table());
        $columns = $wpdb->get_results($wpdb->prepare('SHOW COLUMNS FROM %i', \PPCart_Shopping_Cart_Store::table()), OBJECT_K);
        $this->assertSame(['id', 'cart_key', 'user_id', 'items', 'meta', 'revision', 'created_at', 'updated_at', 'expires_at'], array_keys($columns));
        $this->assertSame('0', $columns['revision']->Default);
        $this->assertSame('UNI', $columns['cart_key']->Key);
        $this->assertNotFalse(wp_next_scheduled('ppcart_shopping_cart_cleanup'));
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-deactivator.php';
        \PPCart_Deactivator::deactivate();
        $this->assertFalse(wp_next_scheduled('ppcart_shopping_cart_cleanup'));
    }

    /** @test-id IT-445 */
    public function test_IT_445_browsing_creates_no_row_and_the_first_add_creates_the_cart(): void
    {
        $id = $this->product('Writer Toolkit', [$this->plan('standard:personal', '39'), $this->plan('team', '59')]);
        $this->assertSame(0, ppcart_get_shopping_cart()->count());
        $this->assertSame(0, $this->rowCount());
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);

        $cart = $this->add($id, 'standard:personal');
        $this->assertSame([$id . ':standard:personal'], $this->keys($cart));
        $this->assertSame(1, $cart->revision);
        $this->assertSame(39.0, $cart->lines[0]->unit_price);
        $this->assertSame('Writer Toolkit', $cart->lines[0]->product_name);
        $this->assertSame('Standard:personal', $cart->lines[0]->plan_name);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $_COOKIE['ppcart_cart']);

        $row = $this->rowByKey($_COOKIE['ppcart_cart']);
        $this->assertSame($cart->cart_id, (int) $row->id);
        $this->assertSame('0', $row->user_id);
        $stored = json_decode($row->items, true);
        $this->assertSame(['product_id', 'plan_id', 'quantity', 'seen_price', 'added_at'], array_keys($stored[0]));
        $this->assertSame([$id, 'standard:personal', 1, '39.00'], array_slice(array_values($stored[0]), 0, 4));
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\.\d{6}Z$/', $stored[0]['added_at']);
        $this->assertGreaterThan(gmdate('Y-m-d H:i:s', time() + 6 * DAY_IN_SECONDS), $row->expires_at);
    }

    /** @test-id IT-445 */
    public function test_IT_445_duplicate_keys_are_refused_and_two_plans_of_one_product_are_two_lines(): void
    {
        $id = $this->product('Writer Toolkit', [$this->plan('standard:personal', '39'), $this->plan('team', '59')]);
        $first = $this->add($id, 'standard:personal');
        $duplicate = $this->add($id, 'standard:personal');
        $this->assertSame('ppcart_cart_already_in_cart', $duplicate->get_error_code());
        $this->assertSame(1, $duplicate->get_error_data()['cart']->count());
        $this->assertSame($first->lines[0]->added_at, $duplicate->get_error_data()['cart']->lines[0]->added_at);
        $this->assertSame(1, $this->store()->read()->revision);

        $second = $this->add($id, 'team');
        $this->assertSame([$id . ':standard:personal', $id . ':team'], $this->keys($second));
        $this->assertSame(98.0, $second->subtotal());
        $this->assertSame(98.0, $second->get_totals()['total']);

        $removed = $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->remove_item($id . ':standard:personal'));
        $this->assertSame([$id . ':team'], $this->keys($removed));
        $again = $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->remove_item($id . ':standard:personal'));
        $this->assertSame([$id . ':team'], $this->keys($again));
        $this->assertSame($removed->revision, $again->revision);
    }

    /** @test-id IT-445 */
    public function test_IT_445_ineligible_offers_are_refused_and_create_no_cart(): void
    {
        $hidden = $this->product('Hidden');
        update_post_meta($hidden, '_ppcart_show_in_shop', 'no');
        $draft = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'draft']);
        $mixed = $this->product('Mixed', [$this->plan('personal', '29', '', 'price_alias'), $this->plan('monthly', '19', 'recurring')]);

        $this->assertSame('ppcart_cart_not_eligible', $this->add($hidden)->get_error_code());
        $this->assertSame('ppcart_cart_not_eligible', $this->add($draft)->get_error_code());
        $this->assertSame('ppcart_cart_plan_not_eligible', $this->add($mixed, 'monthly')->get_error_code());
        $this->assertSame('ppcart_cart_plan_not_eligible', $this->add($mixed, 'price_alias')->get_error_code());
        $this->assertSame('ppcart_cart_plan_not_eligible', $this->add($mixed, 'unknown')->get_error_code());
        update_option('_ppcart_shopping_cart_enable', '0');
        $this->assertSame('ppcart_cart_disabled', $this->add($mixed)->get_error_code());
        delete_option('_ppcart_shopping_cart_enable');
        $this->assertSame(0, $this->rowCount());
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);

        add_filter('ppcart_shopping_cart_max_items', static fn (): int => 1);
        $this->assertSame(1, $this->add($mixed)->count());
        $full = $this->add($this->product('Second'));
        $this->assertSame('ppcart_cart_full', $full->get_error_code());
        $this->assertSame('ppcart_cart_already_in_cart', $this->add($mixed)->get_error_code());
    }

    /** @test-id IT-445 */
    public function test_IT_445_the_cart_charges_the_initial_payment_that_buy_now_charges(): void
    {
        $id = $this->product('Filtered price', [$this->plan('personal', '39'), $this->plan('gift', '15', 'free')]);
        add_filter('ppcart_plan_data', static function (array $plan): array {
            if ('personal' === $plan['option_id']) {
                $plan['initial_payment'] = 12.5;
            }
            return $plan;
        });
        $buyNow = ppcart_plan('personal', 'current', $id);
        $cart = $this->add($id, 'personal');
        $this->assertSame((float) $buyNow->initial_payment, $cart->lines[0]->unit_price);
        $this->assertSame(12.5, $cart->lines[0]->unit_price);
        $this->assertSame(0.0, $this->add($id, 'gift')->lines[1]->unit_price);
    }

    /** @test-id IT-445 */
    public function test_IT_445_a_price_change_is_stored_as_one_notice_and_the_new_price_is_charged(): void
    {
        $id = $this->product('Writer Toolkit', [$this->plan('personal', '19')]);
        $added = $this->add($id);
        update_post_meta($id, '_ppcart_pay_options', [$this->plan('personal', '29')]);

        $repaired = $this->store()->read();
        $this->assertTrue($repaired->changed);
        $this->assertSame($added->revision + 1, $repaired->revision);
        $this->assertSame(29.0, $repaired->lines[0]->unit_price);
        $this->assertCount(1, $repaired->notices);
        $notice = $repaired->notices[0];
        $this->assertSame(['price_changed', $id . ':personal', '19.00', '29.00'], [$notice->code, $notice->key, $notice->old_price, $notice->new_price]);
        $this->assertStringContainsString('The price of Writer Toolkit changed from', $notice->message);
        $this->assertStringContainsString('19.00', $notice->message);
        $this->assertStringContainsString('29.00', $notice->message);
        $this->assertSame('29.00', json_decode($this->rowByKey($_COOKIE['ppcart_cart'])->items, true)[0]['seen_price']);

        $later = $this->store()->read();
        $this->assertFalse($later->changed);
        $this->assertSame($repaired->revision, $later->revision);
        $this->assertCount(1, $later->notices);

        $emptied = $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->remove_item($id . ':personal'));
        $this->assertSame([], $emptied->notices);
    }

    /** @test-id IT-445 */
    public function test_IT_445_a_line_that_stops_being_eligible_is_removed_with_a_notice(): void
    {
        $kept = $this->product('Kept');
        $gone = $this->product('Gone');
        $this->add($kept);
        $cartKey = $_COOKIE['ppcart_cart'];
        $this->add($gone);
        update_post_meta($gone, '_ppcart_show_in_shop', 'no');

        $repaired = $this->store()->read();
        $this->assertTrue($repaired->changed);
        $this->assertSame([$kept . ':personal'], $this->keys($repaired));
        $this->assertSame(['removed', $gone . ':personal'], [$repaired->notices[0]->code, $repaired->notices[0]->key]);
        $this->assertStringContainsString('Gone was removed from your cart', $repaired->notices[0]->message);
        $this->assertNull($repaired->notices[0]->old_price);

        update_post_meta($gone, '_ppcart_show_in_shop', 'yes');
        $this->assertSame([], $this->add($gone)->notices);

        update_post_meta($kept, '_ppcart_show_in_shop', 'no');
        update_post_meta($gone, '_ppcart_show_in_shop', 'no');
        $empty = $this->store()->read();
        $this->assertTrue($empty->is_empty());
        $this->assertCount(2, $empty->notices);
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);
        $this->assertSame('[]', $this->rowByKey($cartKey)->items);
    }

    /** @test-id IT-445 */
    public function test_IT_445_quantity_is_one_unless_a_filter_raises_the_limit(): void
    {
        $id = $this->product('Writer Toolkit');
        $key = $id . ':personal';
        $this->add($id);
        $quantity = static fn (string $key, int $quantity) => static fn (\PPCart_Shopping_Cart $cart) => $cart->set_quantity($key, $quantity);

        $this->assertSame('ppcart_cart_quantity_invalid', $this->store()->update($quantity($key, 2))->get_error_code());
        $this->assertSame('ppcart_cart_quantity_invalid', $this->store()->update($quantity($key, 0))->get_error_code());
        $this->assertSame('ppcart_cart_item_missing', $this->store()->update($quantity('999:personal', 1))->get_error_code());
        $this->assertSame(1, $this->store()->update($quantity($key, 1))->lines[0]->quantity);

        add_filter('ppcart_shopping_cart_item_max_quantity', static fn (): int => 3);
        $raised = $this->store()->update($quantity($key, 3));
        $this->assertSame([3, 3, 87.0], [$raised->lines[0]->quantity, $raised->lines[0]->max_quantity, $raised->lines[0]->subtotal]);
        $this->assertSame('ppcart_cart_quantity_invalid', $this->store()->update($quantity($key, 4))->get_error_code());

        remove_all_filters('ppcart_shopping_cart_item_max_quantity');
        $lowered = $this->store()->read();
        $this->assertTrue($lowered->changed);
        $this->assertSame(1, $lowered->lines[0]->quantity);
    }

    /** @test-id IT-445 */
    public function test_IT_445_stock_caps_a_raised_quantity_limit(): void
    {
        $id = $this->product('Writer Toolkit');
        $key = $id . ':personal';
        $this->add($id);
        $catalog = new \PPCart_Shopping_Cart_Catalog();
        update_post_meta($id, '_ppcart_manage_stock', '1');
        update_post_meta($id, '_ppcart_limit', '4');
        $this->assertSame(1, $catalog->max_quantity($id, 'personal'));

        add_filter('ppcart_shopping_cart_item_max_quantity', static fn (): int => 99);
        $this->assertSame(4, $catalog->max_quantity($id, 'personal'));
        $this->assertSame('ppcart_cart_quantity_invalid', $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->set_quantity($key, 5))->get_error_code());
        $this->assertSame(4, $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->set_quantity($key, 4))->lines[0]->quantity);

        update_post_meta($id, '_ppcart_limit', '0');
        $this->assertSame(1, $catalog->max_quantity($id, 'personal'));
        delete_post_meta($id, '_ppcart_manage_stock');
        $this->assertSame(99, $catalog->max_quantity($id, 'personal'));
    }

    /** @test-id IT-445 */
    public function test_IT_445_extension_meta_is_stored_and_reserved_keys_are_refused(): void
    {
        $this->assertTrue(ppcart_shopping_cart_set_meta('pro_coupon_code', 'SAVE10')->is_empty());
        $this->assertSame(0, $this->rowCount());

        $this->add($this->product('Writer Toolkit'));
        $this->assertSame('SAVE10', ppcart_shopping_cart_set_meta('pro_coupon_code', 'SAVE10')->meta('pro_coupon_code'));
        $this->assertSame('SAVE10', ppcart_get_shopping_cart()->meta('pro_coupon_code'));
        $this->assertSame('ppcart_cart_meta_reserved', ppcart_shopping_cart_set_meta('_notices', [])->get_error_code());
        $this->assertNull(ppcart_shopping_cart_set_meta('pro_coupon_code', null)->meta('pro_coupon_code'));
    }

    /** @test-id IT-445 */
    public function test_IT_445_an_expired_cart_is_not_read_and_the_cleanup_deletes_it(): void
    {
        global $wpdb;
        $this->add($this->product('Writer Toolkit'));
        $cartKey = $_COOKIE['ppcart_cart'];
        $fresh = $this->product('Fresh');
        $wpdb->update(\PPCart_Shopping_Cart_Store::table(), ['expires_at' => gmdate('Y-m-d H:i:s', time() - 60)], ['cart_key' => $cartKey]);

        $this->assertTrue($this->store()->read()->is_empty());
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);
        $this->asGuestWith($cartKey);
        $this->assertSame([$fresh . ':personal'], $this->keys($this->add($fresh)));
        $this->assertNotSame($cartKey, $_COOKIE['ppcart_cart']);

        $this->assertSame(1, \PPCart_Shopping_Cart_Store::delete_expired());
        $this->assertNull($this->rowByKey($cartKey));
        $this->assertNotNull($this->rowByKey($_COOKIE['ppcart_cart']));
        $this->assertSame(10, has_action('ppcart_shopping_cart_cleanup', [\PPCart_Shopping_Cart_Store::class, 'delete_expired']));
    }

    /** @test-id IT-445 */
    public function test_IT_445_a_cart_key_never_opens_the_cart_of_another_owner(): void
    {
        $owner = self::factory()->user->create();
        $other = self::factory()->user->create();
        $id = $this->product('Writer Toolkit');
        wp_set_current_user($owner);
        $this->add($id);
        $ownerKey = $_COOKIE['ppcart_cart'];
        $this->assertSame((string) $owner, $this->rowByKey($ownerKey)->user_id);

        $this->asGuestWith($ownerKey);
        $this->assertTrue($this->store()->read()->is_empty());
        $this->asGuestWith($ownerKey);
        $guest = $this->add($this->product('Guest item'));
        $this->assertNotSame($ownerKey, $_COOKIE['ppcart_cart']);
        $this->assertSame(1, $guest->count());

        wp_set_current_user($other);
        unset($_COOKIE['ppcart_cart']);
        $_COOKIE['ppcart_cart'] = $ownerKey;
        $this->assertTrue($this->store()->read()->is_empty());
        $_COOKIE['ppcart_cart'] = $ownerKey;
        $removed = $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->remove_item($id . ':personal'));
        $this->assertTrue($removed->is_empty());

        $this->assertSame($id, json_decode($this->rowByKey($ownerKey)->items, true)[0]['product_id']);
        wp_set_current_user($owner);
        unset($_COOKIE['ppcart_cart']);
        $this->assertSame([$id . ':personal'], $this->keys($this->store()->read()));
        $this->assertSame($ownerKey, $_COOKIE['ppcart_cart']);
    }
}
