<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartConcurrencyTest extends ShoppingCartTestCase
{
    private function revisionOf(string $cartKey): int
    {
        return (int) $this->rowByKey($cartKey)->revision;
    }

    private function insertRow(array $lines, int $userId, string $cartKey): int
    {
        global $wpdb;
        $now = gmdate('Y-m-d H:i:s');
        $wpdb->insert(\PPCart_Shopping_Cart_Store::table(), [
            'cart_key' => $cartKey,
            'user_id' => $userId,
            'items' => wp_json_encode($lines),
            'meta' => '{}',
            'revision' => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS),
        ]);
        return (int) $wpdb->insert_id;
    }

    private function line(int $productId, string $addedAt): array
    {
        return ['product_id' => $productId, 'plan_id' => 'personal', 'quantity' => 1, 'seen_price' => '29.00', 'added_at' => $addedAt];
    }

    /** @test-id IT-438 */
    public function test_IT_438_two_writers_interleaved_on_one_row_keep_both_lines(): void
    {
        $seed = $this->product('Seed');
        $first = $this->product('Tab one');
        $second = $this->product('Tab two');
        $this->add($seed);
        $cartKey = $_COOKIE['ppcart_cart'];
        $this->assertSame(1, $this->revisionOf($cartKey));

        $attempts = 0;
        $seenByTabOne = [];
        $cart = $this->store()->update(function (\PPCart_Shopping_Cart $cart) use (&$attempts, &$seenByTabOne, $first, $second, $cartKey) {
            $attempts++;
            $seenByTabOne[] = array_column($cart->export()['lines'], 'product_id');
            if (1 === $attempts) {
                $otherTab = $this->store()->update(static fn (\PPCart_Shopping_Cart $other) => $other->add_item($second, 'personal'), true);
                $this->assertSame(2, $otherTab->revision);
                $this->assertSame(2, $this->revisionOf($cartKey));
            }
            return $cart->add_item($first, 'personal');
        }, true);

        $this->assertSame(2, $attempts);
        $this->assertSame([[$seed], [$seed, $second]], $seenByTabOne);
        $this->assertSame([$seed . ':personal', $second . ':personal', $first . ':personal'], $this->keys($cart));
        $this->assertSame(3, $cart->revision);
        $this->assertSame(3, $this->revisionOf($cartKey));
        $this->assertSame([$seed, $second, $first], array_column(json_decode($this->rowByKey($cartKey)->items, true), 'product_id'));
        $this->assertSame(1, $this->rowCount());
    }

    /** @test-id IT-438 */
    public function test_IT_438_two_writers_adding_one_key_leave_one_line(): void
    {
        $seed = $this->product('Seed');
        $shared = $this->product('Shared');
        $this->add($seed);

        $attempts = 0;
        $result = $this->store()->update(function (\PPCart_Shopping_Cart $cart) use (&$attempts, $shared) {
            if (1 === ++$attempts) {
                $this->add($shared);
            }
            return $cart->add_item($shared, 'personal');
        }, true);

        $this->assertSame(2, $attempts);
        $this->assertSame('ppcart_cart_already_in_cart', $result->get_error_code());
        $this->assertSame([$seed . ':personal', $shared . ':personal'], $this->keys($result->get_error_data()['cart']));
        $this->assertSame(2, $this->store()->read()->revision);
    }

    /** @test-id IT-438 */
    public function test_IT_438_a_remove_that_races_an_add_commits_in_write_order(): void
    {
        $seed = $this->product('Seed');
        $added = $this->product('Added');
        $this->add($seed);

        $attempts = 0;
        $cart = $this->store()->update(function (\PPCart_Shopping_Cart $cart) use (&$attempts, $seed, $added) {
            if (1 === ++$attempts) {
                $this->add($added);
            }
            return $cart->remove_item($seed . ':personal');
        });

        $this->assertSame(2, $attempts);
        $this->assertSame([$added . ':personal'], $this->keys($cart));
    }

    /** @test-id IT-438 */
    public function test_IT_438_three_revision_misses_return_a_conflict_with_the_stored_cart(): void
    {
        $seed = $this->product('Seed');
        $wanted = $this->product('Wanted');
        $this->add($seed);

        $attempts = 0;
        $result = $this->store()->update(function (\PPCart_Shopping_Cart $cart) use (&$attempts, $wanted) {
            $attempts++;
            ppcart_shopping_cart_set_meta('other_tab_write', $attempts);
            return $cart->add_item($wanted, 'personal');
        }, true);

        $this->assertSame(3, $attempts);
        $this->assertSame('ppcart_cart_conflict', $result->get_error_code());
        $stored = $result->get_error_data()['cart'];
        $this->assertSame([$seed . ':personal'], $this->keys($stored));
        $this->assertSame(3, $stored->meta('other_tab_write'));
        $this->assertSame(4, $stored->revision);
    }

    /** @test-id IT-438 */
    public function test_IT_438_checkout_claims_one_submit_for_each_revision_the_buyer_saw(): void
    {
        $this->assertSame('ppcart_cart_empty', $this->store()->claim_checkout(0, [])->get_error_code());

        $first = $this->product('First', [$this->plan('personal', '19')]);
        $second = $this->product('Second');
        $this->add($first);
        $seen = $this->add($second)->revision;

        $claimed = $this->store()->claim_checkout($seen, ['email' => 'buyer@example.org']);
        $this->assertSame($seen + 1, $claimed->revision);
        $this->assertSame([$first . ':personal', $second . ':personal'], array_column($claimed->paid_lines(), 'key'));
        $this->assertSame(48.0, $claimed->subtotal());

        $doubleSubmit = $this->store()->claim_checkout($seen, ['email' => 'buyer@example.org']);
        $this->assertSame('ppcart_cart_changed', $doubleSubmit->get_error_code());
        $this->assertSame($seen + 1, $doubleSubmit->get_error_data()['cart']->revision);
        $this->assertSame($seen + 1, $this->store()->read()->revision);

        $seen = $this->store()->read()->revision;
        $this->add($this->product('Added in another tab'));
        $staleForm = $this->store()->claim_checkout($seen, []);
        $this->assertSame('ppcart_cart_changed', $staleForm->get_error_code());
        $this->assertSame(3, $staleForm->get_error_data()['cart']->count());

        $seen = $this->store()->read()->revision;
        update_post_meta($first, '_ppcart_pay_options', [$this->plan('personal', '25')]);
        $repriced = $this->store()->claim_checkout($seen, []);
        $this->assertSame('ppcart_cart_changed', $repriced->get_error_code());
        $shown = $repriced->get_error_data()['cart'];
        $this->assertSame(25.0, $shown->lines[0]->unit_price);
        $this->assertSame('price_changed', $shown->notices[0]->code);
        $this->assertSame($seen + 1, $shown->revision);

        $accepted = $this->store()->claim_checkout($shown->revision, []);
        $this->assertSame($shown->revision + 1, $accepted->revision);
        $this->assertSame(83.0, $accepted->subtotal());
        $this->assertCount(1, $accepted->notices);
    }

    /** @test-id IT-438 */
    public function test_IT_438_two_submits_that_pass_the_gate_together_claim_the_cart_once(): void
    {
        $this->add($this->product('First'));
        $seen = $this->add($this->product('Second'))->revision;

        $otherSubmit = null;
        add_filter('ppcart_shopping_cart_item_max_quantity', $interleave = function (int $max) use (&$otherSubmit, &$interleave, $seen): int {
            remove_filter('ppcart_shopping_cart_item_max_quantity', $interleave);
            $otherSubmit = $this->store()->claim_checkout($seen, []);
            return $max;
        });
        $thisSubmit = $this->store()->claim_checkout($seen, []);

        $this->assertInstanceOf(\PPCart_Shopping_Cart_Priced::class, $otherSubmit);
        $this->assertSame($seen + 1, $otherSubmit->revision);
        $this->assertSame('ppcart_cart_changed', $thisSubmit->get_error_code());
        $this->assertSame($seen + 1, $thisSubmit->get_error_data()['cart']->revision);
        $this->assertSame($seen + 1, $this->store()->read()->revision);
    }

    /** @test-id IT-438 */
    public function test_IT_438_login_merge_keeps_the_user_line_appends_guest_lines_and_deletes_the_guest_row(): void
    {
        $shared = $this->product('Shared');
        $guestOnly = $this->product('Guest only');
        $userOnly = $this->product('User only');
        $user = self::factory()->user->create_and_get();
        $userRow = $this->insertRow([$this->line($shared, '2026-11-01T10:00:00.000000Z'), $this->line($userOnly, '2026-11-01T10:00:01.000000Z')], $user->ID, str_repeat('u', 32));
        $guestLines = [$this->line($shared, '2026-11-20T09:00:00.000000Z'), $this->line($guestOnly, '2026-11-20T09:00:01.000000Z')];
        $this->insertRow($guestLines, 0, str_repeat('g', 32));
        $_COOKIE['ppcart_cart'] = str_repeat('g', 32);

        do_action('wp_login', $user->user_login, $user);

        $merged = json_decode($this->rowByKey(str_repeat('u', 32))->items, true);
        $this->assertSame([$shared, $userOnly, $guestOnly], array_column($merged, 'product_id'));
        $this->assertSame('2026-11-01T10:00:00.000000Z', $merged[0]['added_at']);
        $this->assertSame('2026-11-20T09:00:01.000000Z', $merged[2]['added_at']);
        $this->assertNull($this->rowByKey(str_repeat('g', 32)));
        $this->assertSame(str_repeat('u', 32), $_COOKIE['ppcart_cart']);
        $this->assertSame(2, $this->revisionOf(str_repeat('u', 32)));

        $this->store()->merge_guest_into_user(str_repeat('g', 32), $user->ID);
        $this->assertSame(2, $this->revisionOf(str_repeat('u', 32)));

        $this->insertRow($guestLines, 0, str_repeat('g', 32));
        $this->store()->merge_guest_into_user(str_repeat('g', 32), $user->ID);
        $this->assertSame($merged, json_decode($this->rowByKey(str_repeat('u', 32))->items, true));
        $this->assertSame(2, $this->revisionOf(str_repeat('u', 32)));
        $this->assertNull($this->rowByKey(str_repeat('g', 32)));
        $this->assertSame(1, $this->rowCount());

        wp_set_current_user($user->ID);
        $read = $this->store()->read();
        $this->assertSame($userRow, $read->cart_id);
        $this->assertSame([$shared . ':personal', $userOnly . ':personal', $guestOnly . ':personal'], $this->keys($read));
    }

    /** @test-id IT-438 */
    public function test_IT_438_a_guest_line_added_during_the_login_merge_survives(): void
    {
        $userOnly = $this->product('User only');
        $guestOnly = $this->product('Guest only');
        $late = $this->product('Added during the merge');
        $user = self::factory()->user->create_and_get();
        $this->insertRow([$this->line($userOnly, '2026-11-01T10:00:00.000000Z')], $user->ID, str_repeat('u', 32));
        $this->insertRow([$this->line($guestOnly, '2026-11-20T09:00:00.000000Z')], 0, str_repeat('g', 32));
        $_COOKIE['ppcart_cart'] = str_repeat('g', 32);

        $guestAdd = null;
        add_filter('ppcart_shopping_cart_item_max_quantity', $interleave = function (int $max) use (&$guestAdd, &$interleave, $late): int {
            remove_filter('ppcart_shopping_cart_item_max_quantity', $interleave);
            $guestAdd = $this->add($late);
            return $max;
        });
        do_action('wp_login', $user->user_login, $user);

        $this->assertSame([$guestOnly . ':personal', $late . ':personal'], $this->keys($guestAdd));
        $this->assertSame(2, $guestAdd->revision);
        $this->assertSame([$userOnly, $guestOnly, $late], array_column(json_decode($this->rowByKey(str_repeat('u', 32))->items, true), 'product_id'));
        $this->assertNull($this->rowByKey(str_repeat('g', 32)));
        $this->assertSame(1, $this->rowCount());
        $this->assertSame(str_repeat('u', 32), $_COOKIE['ppcart_cart']);
    }

    /** @test-id IT-438 */
    public function test_IT_438_a_login_merge_that_misses_three_times_keeps_the_guest_row_for_the_next_request(): void
    {
        $userOnly = $this->product('User only');
        $guestOnly = $this->product('Guest only');
        $user = self::factory()->user->create_and_get();
        $this->insertRow([$this->line($userOnly, '2026-11-01T10:00:00.000000Z')], $user->ID, str_repeat('u', 32));
        $this->insertRow([$this->line($guestOnly, '2026-11-20T09:00:00.000000Z')], 0, str_repeat('g', 32));
        $_COOKIE['ppcart_cart'] = str_repeat('g', 32);

        $writing = false;
        $writes = 0;
        add_filter('ppcart_shopping_cart_item_max_quantity', $interleave = function (int $max) use (&$writing, &$writes): int {
            if (! $writing) {
                $writing = true;
                ppcart_shopping_cart_set_meta('guest_write', ++$writes);
                $writing = false;
            }
            return $max;
        });
        do_action('wp_login', $user->user_login, $user);
        remove_filter('ppcart_shopping_cart_item_max_quantity', $interleave);

        $guestRow = $this->rowByKey(str_repeat('g', 32));
        $this->assertNotNull($guestRow);
        $this->assertSame($writes, json_decode($guestRow->meta, true)['guest_write']);
        $this->assertSame(str_repeat('g', 32), $_COOKIE['ppcart_cart']);
        $this->assertSame(2, $this->rowCount());

        $this->store()->merge_guest_into_user(str_repeat('g', 32), $user->ID);
        $this->assertSame([$userOnly, $guestOnly], array_column(json_decode($this->rowByKey(str_repeat('u', 32))->items, true), 'product_id'));
        $this->assertNull($this->rowByKey(str_repeat('g', 32)));
        $this->assertSame(str_repeat('u', 32), $_COOKIE['ppcart_cart']);
    }

    /** @test-id IT-438 */
    public function test_IT_438_a_user_without_a_cart_takes_over_the_guest_row_at_login(): void
    {
        $id = $this->product('Guest item');
        $guest = $this->add($id);
        $cartKey = $_COOKIE['ppcart_cart'];
        $user = self::factory()->user->create_and_get();

        do_action('wp_login', $user->user_login, $user);

        $row = $this->rowByKey($cartKey);
        $this->assertSame([$guest->cart_id, (string) $user->ID], [(int) $row->id, $row->user_id]);
        $this->assertSame(1, $this->rowCount());
        wp_set_current_user($user->ID);
        $this->assertSame([$id . ':personal'], $this->keys($this->store()->read()));
        $this->asGuestWith($cartKey);
        $this->assertTrue($this->store()->read()->is_empty());
    }

    /** @test-id IT-438 */
    public function test_IT_438_a_signed_in_request_with_a_guest_cookie_merges_without_the_login_hook(): void
    {
        $guestItem = $this->product('Guest item');
        $userItem = $this->product('User item');
        $user = self::factory()->user->create();
        $this->insertRow([$this->line($userItem, '2026-11-01T10:00:00.000000Z')], $user, str_repeat('u', 32));
        $this->insertRow([$this->line($guestItem, '2026-11-20T09:00:00.000000Z')], 0, str_repeat('g', 32));

        wp_set_current_user($user);
        $_COOKIE['ppcart_cart'] = str_repeat('g', 32);
        $cart = $this->store()->read();

        $this->assertSame([$userItem . ':personal', $guestItem . ':personal'], $this->keys($cart));
        $this->assertNull($this->rowByKey(str_repeat('g', 32)));
        $this->assertSame(str_repeat('u', 32), $_COOKIE['ppcart_cart']);
    }

    /** @test-id IT-438 */
    public function test_IT_438_two_rows_of_one_user_merge_into_the_oldest(): void
    {
        $older = $this->product('Older');
        $newer = $this->product('Newer');
        $user = self::factory()->user->create();
        $oldest = $this->insertRow([$this->line($older, '2026-11-01T10:00:00.000000Z')], $user, str_repeat('a', 32));
        $this->insertRow([$this->line($newer, '2026-11-02T10:00:00.000000Z'), $this->line($older, '2026-11-02T10:00:01.000000Z')], $user, str_repeat('b', 32));

        wp_set_current_user($user);
        $cart = $this->store()->read();

        $this->assertSame($oldest, $cart->cart_id);
        $this->assertSame([$older . ':personal', $newer . ':personal'], $this->keys($cart));
        $this->assertSame('2026-11-01T10:00:00.000000Z', $cart->lines[0]->added_at);
        $this->assertNull($this->rowByKey(str_repeat('b', 32)));
        $this->assertSame(1, $this->rowCount());
    }

    /** @test-id IT-438 */
    public function test_IT_438_paid_lines_leave_the_cart_and_a_line_added_later_stays(): void
    {
        $paidOnly = $this->product('Paid only');
        $readded = $this->product('Added again');
        $late = $this->product('Added during payment');
        $this->add($paidOnly);
        $cartKey = $_COOKIE['ppcart_cart'];
        $order = $this->add($readded);
        $paid = $order->paid_lines();

        $this->store()->update(static fn (\PPCart_Shopping_Cart $cart) => $cart->remove_item($readded . ':personal'));
        $this->add($readded);
        $this->add($late);
        $this->asGuestWith();

        $this->store()->remove_paid_lines($order->cart_id, $paid);
        $this->assertSame([$readded, $late], array_column(json_decode($this->rowByKey($cartKey)->items, true), 'product_id'));
        $revision = $this->revisionOf($cartKey);

        $this->store()->remove_paid_lines($order->cart_id, $paid);
        $this->assertSame($revision, $this->revisionOf($cartKey));
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);
    }
}
