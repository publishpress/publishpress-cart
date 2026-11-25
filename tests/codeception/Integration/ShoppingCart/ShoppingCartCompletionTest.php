<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartCompletionTest extends ShoppingCartTestCase
{
    private int $toolkit;

    private int $course;

    private array $seen = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seen = ['status' => [], 'primary' => [], 'after' => [], 'integrations' => [], 'notifications' => [], 'mail' => []];
        $this->toolkit = $this->product('Writer Toolkit');
        $this->course = $this->product('Editing Course', [$this->plan('solo', '39'), $this->plan('team', '59')]);
        foreach ([$this->toolkit, $this->course] as $id) {
            update_post_meta($id, '_ppcart_integrations', [['services' => 'recorder', 'service_trigger' => ['purchased', 'pending', 'refunded']]]);
            update_post_meta($id, '_ppcart_notifications', [['subject' => 'Sold']]);
            update_post_meta($id, '_ppcart_files', [['file_id' => 'file-' . $id, 'file_name' => 'Download', 'file_url' => 'https://example.org/file.pdf']]);
        }
        update_post_meta($this->course, '_ppcart_manage_stock', '1');
        update_post_meta($this->course, '_ppcart_limit', '5');

        foreach (['ppcart_order_pending', 'ppcart_order_complete', 'ppcart_order_refunded'] as $hook) {
            add_action($hook, function ($status, $info, $type) use ($hook): void {
                $this->seen['status'][] = [$hook, $status, $info['checkout_type'] ?? null, (int) $info['product_id'], $type];
            }, 1, 3);
        }
        add_action('ppcart_order_after_primary_integration_action', function ($action, $status, $info, $type, $event, $order): void {
            $this->seen['primary'][] = [$action, $type];
            if ('main' !== $type) {
                return;
            }
            foreach ($order->get_items() ?: [] as $item) {
                if ($info['product_id'] != $item->product_id) {
                    do_action($action, $status, ['product_id' => $item->product_id, 'order_type' => 'bump'] + $info, 'bump');
                }
            }
        }, 10, 6);
        add_action('ppcart_run_after_integrations', function ($status) {
            $this->seen['after'][] = $status;
        });
        add_action('ppcart_recorder_integrations', function ($integration, $productId, $order): void {
            $this->seen['integrations'][] = [$integration['service_trigger'], (int) $productId, $order['option_id'], (float) $order['amount'], $order['ID']];
        }, 10, 3);
        add_filter('ppcart_should_send_product_notification', function ($send, $notification, $info): bool {
            $this->seen['notifications'][] = [$info['product_id'], $info['item_name']];
            return false;
        }, 10, 3);
        add_filter('pre_wp_mail', function ($result, array $mail): bool {
            if ('buyer@example.org' === $mail['to']) {
                $this->seen['mail'][] = [$mail['subject'], $mail['message']];
            }
            return true;
        }, 10, 2);
        update_option('_ppcart_email_confirmation_enable', '1');
        update_option('_ppcart_email_confirmation_subject', 'Receipt: {order_inline_list}');
        update_option('_ppcart_email_confirmation_body', '[{order_list}] [{product_list}] [{product_inline_list}]');
    }

    protected function tearDown(): void
    {
        foreach (['enable', 'subject', 'body'] as $option) {
            delete_option('_ppcart_email_confirmation_' . $option);
        }
        parent::tearDown();
    }

    private function checkout(string $payMethod): int
    {
        $this->add($this->toolkit);
        $cart = $this->add($this->course, 'team');
        $claimed = $this->store()->claim_checkout($cart->revision, ['email' => 'buyer@example.org']);
        $order = new \PPCart_Order();
        $order->load_from_shopping_cart($claimed, ['email' => 'buyer@example.org', 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'pay-method' => $payMethod]);
        return (int) ppcart_store_stripe_owned_record($order);
    }

    private function setStatus(int $orderId, string $status): void
    {
        $order = new \PPCart_Order($orderId);
        $order->status = $status;
        $order->payment_status = $status;
        ppcart_store_stripe_owned_record($order);
    }

    private function downloads(int $orderId): array
    {
        global $wpdb;
        return array_map(
            static fn (object $row): array => [(int) $row->product_id, (string) $row->downloads_remaining],
            $wpdb->get_results($wpdb->prepare('SELECT product_id, downloads_remaining FROM %i WHERE order_id = %d ORDER BY download_id', ppcart_live_table('downloads'), $orderId))
        );
    }

    /** @test-id IT-452 */
    public function test_IT_452_paid_cart_order_runs_every_item_once_and_sends_one_email(): void
    {
        update_post_meta($this->toolkit, '_ppcart_disable_purchase_email', '1');
        $late = $this->product('Late Addition');
        $id = $this->checkout('stripe');
        $this->assertSame([], $this->seen['status']);
        $this->assertSame([[$this->toolkit, 'unlimited'], [$this->course, 'unlimited']], $this->downloads($id));
        $this->assertCount(2, $this->store()->read()->lines);
        $this->add($late);

        $this->setStatus($id, 'paid');
        $this->assertSame([['ppcart_order_complete', 'paid', 'cart', 0, 'main']], $this->seen['status']);
        $this->assertSame([['ppcart_order_complete', 'cart']], $this->seen['primary']);
        $this->assertSame(['pending-payment', 'paid'], $this->seen['after']);
        $this->assertSame(
            [['purchased', $this->toolkit, 'personal', 29.0, $id], ['purchased', $this->course, 'team', 59.0, $id]],
            $this->seen['integrations']
        );
        $this->assertSame([[$this->toolkit, 'Personal'], [$this->course, 'Team']], $this->seen['notifications']);
        $this->assertSame('4', get_post_meta($this->course, '_ppcart_limit', true));
        $this->assertSame([$late . ':personal'], $this->keys($this->store()->read()));

        $this->assertCount(1, $this->seen['mail']);
        [$subject, $message] = $this->seen['mail'][0];
        $this->assertSame('Receipt: Writer Toolkit - Personal, Editing Course - Team', $subject);
        $this->assertStringContainsString('[Writer Toolkit - Personal<br />Editing Course - Team] [Writer Toolkit<br />Editing Course] [Writer Toolkit, Editing Course]', $message);

        $this->setStatus($id, 'paid');
        $this->assertCount(1, $this->seen['status']);
        $this->assertCount(1, $this->seen['mail']);
        $this->assertSame('4', get_post_meta($this->course, '_ppcart_limit', true));
    }

    /** @test-id IT-452 */
    public function test_IT_452_cash_on_delivery_order_runs_pending_work_for_every_item_and_empties_the_cart(): void
    {
        $id = $this->checkout('cod');
        $this->assertSame([['ppcart_order_pending', 'pending', 'cart', 0, 'main']], $this->seen['status']);
        $this->assertSame([['ppcart_order_pending', 'cart']], $this->seen['primary']);
        $this->assertSame(
            [['pending', $this->toolkit, 'personal', 29.0, $id], ['pending', $this->course, 'team', 59.0, $id]],
            $this->seen['integrations']
        );
        $this->assertSame([[$this->toolkit, 'Personal'], [$this->course, 'Team']], $this->seen['notifications']);
        $this->assertSame('5', get_post_meta($this->course, '_ppcart_limit', true));
        $this->assertTrue($this->store()->read()->is_empty());
    }

    /** @test-id IT-452 */
    public function test_IT_452_refund_revokes_every_item_download_and_restocks_every_item(): void
    {
        $id = $this->checkout('cod');
        $this->setStatus($id, 'paid');
        $this->assertSame('4', get_post_meta($this->course, '_ppcart_limit', true));
        $this->seen['integrations'] = $this->seen['status'] = [];

        $this->assertSame('OK', ppcart_order_refund(['id' => $id, 'restock' => 'YES']));
        $this->assertSame([['ppcart_order_refunded', 'refunded', 'cart', 0, 'main']], $this->seen['status']);
        $this->assertSame([[$this->toolkit, '0'], [$this->course, '0']], $this->downloads($id));
        $this->assertSame(
            [['refunded', $this->toolkit, 'personal', 29.0, $id], ['refunded', $this->course, 'team', 59.0, $id]],
            $this->seen['integrations']
        );
        $this->assertSame('5', get_post_meta($this->course, '_ppcart_limit', true));
    }

    /** @test-id IT-452 */
    public function test_IT_452_buy_now_order_still_replays_for_an_item_of_another_product(): void
    {
        $order = new \PPCart_Order();
        foreach (['product_id' => $this->toolkit, 'product_name' => 'Writer Toolkit', 'option_id' => 'personal', 'plan_id' => 'personal', 'email' => 'buyer@example.org', 'pay_method' => 'cod', 'status' => 'paid', 'amount' => 88.0] as $key => $value) {
            $order->$key = $value;
        }
        $order->items = [
            ['item_type' => 'main', 'product_id' => $this->toolkit, 'price_id' => 'personal', 'total_amount' => 29.0, 'quantity' => 1],
            ['item_type' => 'bump', 'product_id' => $this->course, 'price_id' => 'team', 'total_amount' => 59.0, 'quantity' => 1],
        ];
        $order->store();

        $this->assertSame([['ppcart_order_complete', 'main']], $this->seen['primary']);
        $this->assertSame(
            [['ppcart_order_complete', 'paid', null, $this->toolkit, 'main'], ['ppcart_order_complete', 'paid', null, $this->course, 'bump']],
            $this->seen['status']
        );
        $this->assertSame([$this->toolkit, $this->course], array_column($this->seen['integrations'], 1));
        $this->assertSame('4', get_post_meta($this->course, '_ppcart_limit', true));
    }

    /** @test-id IT-452 */
    public function test_IT_452_item_context_tags_list_one_item_and_reports_split_revenue_by_product(): void
    {
        $this->add($this->course, 'solo');
        $id = $this->checkout('cod');
        $data = (new \PPCart_Order($id))->get_data();
        $this->assertSame('Editing Course - Solo, Writer Toolkit - Personal, Editing Course - Team', ppcart_personalize('{order_inline_list}', $data));
        $this->assertSame('Editing Course - Solo', ppcart_personalize('{order_inline_list}', ppcart_shopping_cart_order_contexts($data)[0]));
        $this->assertSame('Editing Course', ppcart_personalize('{product_list}', ppcart_shopping_cart_order_contexts($data)[0]));

        $this->assertSame([$this->course => 98.0, $this->toolkit => 29.0], ppcart_shopping_cart_order_revenue(new \PPCart_Order($id)));
        $buyNow = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_status' => 'paid']);
        $this->assertSame([], ppcart_shopping_cart_order_revenue(new \PPCart_Order($buyNow)));
    }

    /** @test-id IT-452 */
    public function test_IT_452_customer_bought_product_finds_an_item_of_a_paid_cart_order(): void
    {
        $bought = fn (string $attributes) => do_shortcode('[ppcart_customer_bought_product email="buyer@example.org" ' . $attributes . ']');
        $id = $this->checkout('stripe');
        $this->assertSame('', $bought('product_id="' . $this->course . '"'));

        $this->setStatus($id, 'paid');
        $this->assertSame((string) $id, $bought('product_id="' . $this->course . '"'));
        $this->assertSame((string) $id, $bought('product_id="' . $this->toolkit . '"'));
        $this->assertSame((string) $id, $bought('product_id="' . $this->course . '" plan_id="team"'));
        $this->assertSame('', $bought('product_id="' . $this->course . '" plan_id="solo"'));
        $this->assertSame('', $bought('product_id="' . $this->product('Never Bought') . '"'));
        $this->assertSame('', do_shortcode('[ppcart_customer_bought_product email="other@example.org" product_id="' . $this->course . '"]'));
    }
}
