<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartRestTest extends ShoppingCartTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = null;
        rest_get_server();
    }

    protected function tearDown(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;
        delete_option('_ppcart_shopping_cart_enable');
        delete_option('_ppcart_after_add_to_cart');
        remove_all_filters('ppcart_shopping_cart_totals');
        parent::tearDown();
    }

    private function request(string $method, string $route, array $body = [], bool $withNonce = true): \WP_REST_Response
    {
        $request = new \WP_REST_Request($method, '/ppcart/v1' . $route);
        if ($withNonce) {
            $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        }
        $request->set_body_params($body);
        $server = rest_get_server();
        return apply_filters('rest_post_dispatch', rest_ensure_response($server->dispatch($request)), $server, $request);
    }

    /** @test-id IT-439 */
    public function test_IT_439_reading_an_empty_cart_returns_the_shape_and_a_nonce_without_a_row(): void
    {
        $response = $this->request('GET', '/cart', [], false);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertSame(['revision', 'count', 'currency', 'items', 'totals', 'notices', 'after_add', 'links', 'nonce'], array_keys($data));
        $this->assertSame([0, 0, [], []], [$data['revision'], $data['count'], $data['items'], $data['notices']]);
        $this->assertSame('mini_cart', $data['after_add']);
        $this->assertSame(['shop', 'cart', 'checkout'], array_keys($data['links']));
        $this->assertSame(['0.00', '0.00'], [$data['totals']['subtotal'], $data['totals']['total']]);
        $this->assertSame(1, wp_verify_nonce($data['nonce'], 'wp_rest'));
        $this->assertSame('private, no-store', $response->get_headers()['Cache-Control']);
        $this->assertSame(0, $this->rowCount());
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);
    }

    /** @test-id IT-439 */
    public function test_IT_439_adding_returns_server_prices_as_decimal_strings_and_hides_storage_fields(): void
    {
        $id = $this->product('Writer Toolkit', [$this->plan('standard:personal', '39')]);
        $attachment = self::factory()->attachment->create(['file' => 'toolkit.jpg', 'post_mime_type' => 'image/jpeg']);
        set_post_thumbnail($id, $attachment);
        $other = $this->product('Second');

        $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'standard:personal', 'unit_price' => '0.01']);
        $response = $this->request('POST', '/cart/items', ['product_id' => (string) $other, 'plan_id' => 'personal']);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertSame([2, 2], [$data['revision'], $data['count']]);
        $item = $data['items'][0];
        $this->assertSame(
            ['key', 'product_id', 'plan_id', 'product_name', 'plan_name', 'quantity', 'max_quantity', 'unit_price', 'subtotal', 'price_html', 'image', 'permalink'],
            array_keys($item)
        );
        $this->assertSame([$id . ':standard:personal', $id, 'standard:personal', 'Writer Toolkit', 1, 1, '39.00', '39.00'], [$item['key'], $item['product_id'], $item['plan_id'], $item['product_name'], $item['quantity'], $item['max_quantity'], $item['unit_price'], $item['subtotal']]);
        $this->assertStringContainsString('39.00', $item['price_html']);
        $this->assertSame('Writer Toolkit', $item['image']['alt']);
        $this->assertStringEndsWith('/toolkit.jpg', $item['image']['url']);
        $this->assertSame(get_permalink($id), $item['permalink']);
        $this->assertNull($data['items'][1]['image']);
        $this->assertSame(['68.00', '68.00'], [$data['totals']['subtotal'], $data['totals']['total']]);
        $this->assertSame(['subtotal', 'total'], array_column($data['totals']['rows'], 'type'));
        $this->assertSame('68.00', $data['totals']['rows'][1]['amount']);
        $this->assertStringContainsString('68.00', $data['totals']['rows'][1]['html']);
        $json = wp_json_encode($data);
        foreach (['cart_key', 'user_id', 'seen_price', $_COOKIE['ppcart_cart']] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
        $this->assertSame('private, no-store', $response->get_headers()['Cache-Control']);
    }

    /** @test-id IT-439 */
    public function test_IT_439_changes_need_a_rest_nonce_and_reading_does_not(): void
    {
        $id = $this->product('Writer Toolkit');
        $missing = $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'personal'], false);
        $this->assertSame([403, 'rest_cookie_invalid_nonce'], [$missing->get_status(), $missing->get_data()['code']]);

        $stale = new \WP_REST_Request('POST', '/ppcart/v1/cart/items');
        $stale->set_header('X-WP-Nonce', 'stale-nonce');
        $stale->set_body_params(['product_id' => $id, 'plan_id' => 'personal']);
        $this->assertSame(403, rest_get_server()->dispatch($stale)->get_status());
        $this->assertSame(0, $this->rowCount());

        $this->assertSame(200, $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'personal'])->get_status());
        foreach (['DELETE', 'PATCH'] as $method) {
            $this->assertSame(403, $this->request($method, '/cart/items/' . $id . ':personal', ['quantity' => 1], false)->get_status());
        }
        $this->assertSame(1, $this->request('GET', '/cart', [], false)->get_data()['count']);
    }

    /** @test-id IT-439 */
    public function test_IT_439_cart_errors_carry_their_status_and_the_current_cart(): void
    {
        $id = $this->product('Mixed', [$this->plan('personal', '29'), $this->plan('monthly', '19', 'recurring')]);
        $hidden = $this->product('Hidden');
        update_post_meta($hidden, '_ppcart_show_in_shop', 'no');
        $error = fn (\WP_REST_Response $response): array => [$response->get_status(), $response->get_data()['code']];

        $plan = $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'monthly']);
        $this->assertSame([422, 'ppcart_cart_plan_not_eligible'], $error($plan));
        $this->assertSame(0, $plan->get_data()['data']['cart']['count']);
        $this->assertSame(1, wp_verify_nonce($plan->get_data()['data']['cart']['nonce'], 'wp_rest'));
        $this->assertSame('private, no-store', $plan->get_headers()['Cache-Control']);
        $this->assertSame([422, 'ppcart_cart_not_eligible'], $error($this->request('POST', '/cart/items', ['product_id' => $hidden, 'plan_id' => 'personal'])));
        $this->assertSame(0, $this->rowCount());

        $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'personal']);
        $duplicate = $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'personal']);
        $this->assertSame([409, 'ppcart_cart_already_in_cart'], $error($duplicate));
        $this->assertSame([$id . ':personal'], array_column($duplicate->get_data()['data']['cart']['items'], 'key'));
        $this->assertSame(1, $duplicate->get_data()['data']['cart']['revision']);

        $this->assertSame([422, 'ppcart_cart_quantity_invalid'], $error($this->request('PATCH', '/cart/items/' . $id . ':personal', ['quantity' => 2])));
        $this->assertSame(200, $this->request('PATCH', '/cart/items/' . $id . ':personal', ['quantity' => 1])->get_status());
        $this->assertSame([404, 'ppcart_cart_item_missing'], $error($this->request('PATCH', '/cart/items/999:personal', ['quantity' => 1])));

        $this->assertSame(400, $this->request('POST', '/cart/items', ['product_id' => 'abc', 'plan_id' => 'personal'])->get_status());
        $this->assertSame(400, $this->request('POST', '/cart/items', ['product_id' => $id])->get_status());
        $this->assertSame(400, $this->request('PATCH', '/cart/items/' . $id . ':personal', ['quantity' => 'many'])->get_status());
        $this->assertSame(400, $this->request('DELETE', '/cart/items/no-colon')->get_status());

        update_option('_ppcart_shopping_cart_enable', '0');
        $this->assertSame([403, 'ppcart_cart_disabled'], $error($this->request('POST', '/cart/items', ['product_id' => $hidden, 'plan_id' => 'personal'])));
    }

    /** @test-id IT-439 */
    public function test_IT_439_removing_a_line_succeeds_also_when_the_line_is_missing(): void
    {
        $id = $this->product('Writer Toolkit', [$this->plan('standard:personal', '39')]);
        $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'standard:personal']);

        $removed = $this->request('DELETE', '/cart/items/' . $id . ':standard:personal');
        $this->assertSame([200, 0], [$removed->get_status(), $removed->get_data()['count']]);
        $this->assertArrayNotHasKey('ppcart_cart', $_COOKIE);
        $again = $this->request('DELETE', '/cart/items/' . $id . ':standard:personal');
        $this->assertSame([200, 0], [$again->get_status(), $again->get_data()['count']]);
    }

    /** @test-id IT-439 */
    public function test_IT_439_the_totals_filter_and_stored_notices_reach_the_response(): void
    {
        $id = $this->product('Writer Toolkit', [$this->plan('personal', '19')]);
        $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'personal']);
        update_post_meta($id, '_ppcart_pay_options', [$this->plan('personal', '29')]);
        update_option('_ppcart_after_add_to_cart', 'checkout');
        add_filter('ppcart_shopping_cart_totals', static function (array $totals, \PPCart_Shopping_Cart_Priced $cart): array {
            array_splice($totals['rows'], 1, 0, [['type' => 'discount', 'label' => '<b>Coupon</b>', 'amount' => -5.0]]);
            $totals['total'] = $cart->subtotal() - 5.0;
            $totals['rows'][2]['amount'] = $totals['total'];
            return $totals;
        }, 10, 2);

        $data = $this->request('GET', '/cart', [], false)->get_data();

        $this->assertSame(['29.00', '24.00'], [$data['totals']['subtotal'], $data['totals']['total']]);
        $this->assertSame([['discount', 'Coupon', '-5.00'], ['total', 'Total', '24.00']], array_map(static fn (array $row): array => [$row['type'], $row['label'], $row['amount']], array_slice($data['totals']['rows'], 1)));
        $this->assertSame(['code', 'key', 'message', 'old_price', 'new_price'], array_keys($data['notices'][0]));
        $this->assertSame(['price_changed', $id . ':personal', '19.00', '29.00'], [$data['notices'][0]['code'], $data['notices'][0]['key'], $data['notices'][0]['old_price'], $data['notices'][0]['new_price']]);
        $this->assertSame('checkout', $data['after_add']);
        $this->assertSame(2, $data['revision']);
    }

    /** @test-id IT-439 */
    public function test_IT_439_a_signed_in_visitor_reads_and_changes_only_the_own_cart(): void
    {
        $id = $this->product('Writer Toolkit');
        $owner = self::factory()->user->create();
        wp_set_current_user($owner);
        $this->request('POST', '/cart/items', ['product_id' => $id, 'plan_id' => 'personal']);
        $ownerKey = $_COOKIE['ppcart_cart'];
        $this->assertSame((string) $owner, $this->rowByKey($ownerKey)->user_id);

        $this->asGuestWith($ownerKey);
        $this->assertSame(0, $this->request('GET', '/cart', [], false)->get_data()['count']);
        $this->asGuestWith($ownerKey);
        $this->assertSame(200, $this->request('DELETE', '/cart/items/' . $id . ':personal')->get_status());
        $this->assertSame($id, json_decode($this->rowByKey($ownerKey)->items, true)[0]['product_id']);

        wp_set_current_user($owner);
        unset($_COOKIE['ppcart_cart']);
        $this->assertSame(1, $this->request('GET', '/cart', [], false)->get_data()['count']);
    }
}
