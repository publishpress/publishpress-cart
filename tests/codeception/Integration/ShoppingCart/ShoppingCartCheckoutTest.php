<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartCheckoutTest extends ShoppingCartTestCase
{
    private const BUYER = ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'buyer@example.org', 'pay-method' => 'stripe'];

    private object $stripe;

    private int $toolkit;

    private int $course;

    private int $completed = 0;

    private array $globals = [];

    protected function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = null;
        rest_get_server();

        $this->globals = ['ppcart_stripe' => $GLOBALS['ppcart_stripe'] ?? null, 'ppcart_currency' => $GLOBALS['ppcart_currency'] ?? null, '_GET' => $_GET, '_POST' => $_POST];
        $GLOBALS['ppcart_stripe'] = ['mode' => 'test', 'pk' => 'pk_test_cart', 'sk' => 'sk_test_cart'];
        $GLOBALS['ppcart_currency'] = 'USD';
        update_option('_ppcart_stripe_enable', '1');
        update_option('_ppcart_stripe_connect_account_id_test', 'acct_cartqa');

        $this->stripe = new class {
            public array $created = [];
            public string $status = 'requires_payment_method';
            public ?string $error = null;
            public ?int $orderId = null;
            public object $paymentIntents;

            public function __construct()
            {
                $this->paymentIntents = new class ($this) {
                    public function __construct(private object $stripe)
                    {
                    }

                    public function create(array $args, array $options = []): object
                    {
                        if ($this->stripe->error) {
                            throw new \Exception($this->stripe->error);
                        }
                        $this->stripe->created[] = [$args, $options];
                        return (object) ['id' => 'pi_cart_' . count($this->stripe->created), 'client_secret' => 'pi_cart_secret_' . count($this->stripe->created)];
                    }

                    public function retrieve(string $id): object
                    {
                        $args = end($this->stripe->created)[0];
                        return (object) ['id' => $id, 'status' => $this->stripe->status, 'metadata' => (object) (['ppcart_order_id' => $this->stripe->orderId ?? $args['metadata']['ppcart_order_id']] + $args['metadata'])];
                    }
                };
            }
        };

        $this->toolkit = $this->product('Writer Toolkit');
        $this->course = $this->product('Editing Course', [$this->plan('solo', '39'), $this->plan('team', '59')]);
        add_action('ppcart_order_complete', function (): void {
            $this->completed++;
        });
    }

    protected function tearDown(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;
        [$GLOBALS['ppcart_stripe'], $GLOBALS['ppcart_currency'], $_GET, $_POST] = array_values($this->globals);
        foreach (['_ppcart_stripe_enable', '_ppcart_stripe_connect_account_id_test', '_ppcart_cashondelivery_enable', '_ppcart_shopping_cart_enable', '_ppcart_cart_checkout_phone', '_ppcart_cart_page_id', '_ppcart_cart_checkout_terms', '_ppcart_cart_checkout_terms_url', '_ppcart_cart_confirmation_message', '_ppcart_cart_confirmation', '_ppcart_cart_redirect'] as $option) {
            delete_option($option);
        }
        parent::tearDown();
    }

    private function controller(): \PPCart_Public_Cart_Checkout_Controller
    {
        return new \PPCart_Public_Cart_Checkout_Controller(stripe: new \PPCart_Shopping_Cart_Stripe(fn (): object => $this->stripe));
    }

    private function cart(): \PPCart_Shopping_Cart_Priced
    {
        $this->add($this->toolkit);
        return $this->add($this->course, 'team');
    }

    private function checkout(array $body)
    {
        $request = new \WP_REST_Request('POST', '/ppcart/v1/cart/checkout');
        $request->set_query_params(['fragments' => 'checkout']);
        $request->set_body_params($body);
        return $this->controller()->checkout($request);
    }

    private function confirm(int $orderId, string $token)
    {
        $request = new \WP_REST_Request('POST', '/ppcart/v1/cart/checkout/confirm');
        $request->set_body_params(['order_id' => $orderId, 'token' => $token]);
        return $this->controller()->confirm($request);
    }

    private function orderCount(): int
    {
        return count(get_posts(['post_type' => ppcart_live_post_type('order'), 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 50]));
    }

    /** @test-id IT-453 */
    public function test_IT_453_stripe_checkout_stores_one_order_and_one_payment_intent_for_the_order_total(): void
    {
        $cart = $this->cart();
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $response = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $cart->revision, 'ppcart_page_id' => $page, 'ppcart_product_id' => 999, 'amount' => '1.00']);
        $this->assertNotWPError($response);
        $data = $response->get_data();
        $order = new \PPCart_Order($data['order_id']);

        $this->assertSame(1, $this->orderCount());
        $this->assertSame(['cart', 'pending-payment', 'stripe', 'test', 88.0, 'pi_cart_1'], [$order->checkout_type, $order->status, $order->pay_method, $order->gateway_mode, (float) $order->amount, $order->transaction_id]);
        $this->assertSame([$this->toolkit, $this->course], array_map(static fn ($item): int => (int) $item->product_id, $order->get_items()));

        $this->assertCount(1, $this->stripe->created);
        [$args, $options] = $this->stripe->created[0];
        $this->assertSame([8800, 'USD', ['card', 'link'], 'Order #' . $order->id . ' (2 items)'], [$args['amount'], $args['currency'], $args['payment_method_types'], $args['description']]);
        $this->assertSame(['ppcart_checkout_type' => 'cart', 'ppcart_order_id' => $order->id, 'ppcart_item_count' => 2, 'origin' => get_site_url()], $args['metadata']);
        $this->assertArrayNotHasKey('ppcart_product_id', $args['metadata']);
        $this->assertSame(['destination' => 'acct_cartqa'], $args['transfer_data']);
        $this->assertSame((int) round(8800 * PPCART_STRIPE_CONNECT_EXTRA_PERCENT / 100), $args['application_fee_amount']);
        $this->assertSame(['idempotency_key' => 'ppcart-cart-order-' . $order->id], $options);

        $this->assertSame('requires_payment', $data['status']);
        $this->assertTrue(\PPCart_Order::token_matches($order->id, $data['token']));
        $this->assertSame(['client_secret' => 'pi_cart_secret_1', 'amount' => 8800, 'currency' => 'usd', 'return_url' => \PPCart_Order::confirmation_url(get_permalink($page), $order->id)], $data['payment']);
        $this->assertSame('message', $data['confirmation']['type']);
        $this->assertStringStartsWith(get_permalink($page), $data['confirmation']['url']);
        $this->assertStringContainsString('ppcart-order=' . $order->id . '&token=' . $data['token'], $data['confirmation']['url']);
        $this->assertSame([2, $cart->revision + 1], [$data['cart']['count'], $data['cart']['revision']]);
        $this->assertStringContainsString('data-ppcart-amount="8800"', $data['cart']['fragments']['checkout']);
        $this->assertSame(0, $this->completed);
    }

    /** @test-id IT-453 */
    public function test_IT_453_order_is_paid_only_when_stripe_reports_the_payment_of_that_order(): void
    {
        $data = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $this->cart()->revision])->get_data();
        $orderId = $data['order_id'];

        $waiting = $this->confirm($orderId, $data['token']);
        $this->assertSame(['ppcart_cart_payment_incomplete', 402], [$waiting->get_error_code(), $waiting->get_error_data()['status']]);
        $this->stripe->status = 'succeeded';
        $this->stripe->orderId = $orderId + 1;
        $this->assertSame('ppcart_cart_payment_incomplete', $this->confirm($orderId, $data['token'])->get_error_code());
        $this->assertSame('ppcart_cart_order_missing', $this->confirm($orderId, 'wrong-token')->get_error_code());
        $this->assertSame(['pending-payment', 0, 2], [(new \PPCart_Order($orderId))->status, $this->completed, $this->store()->read()->count()]);

        $this->stripe->orderId = null;
        $paid = $this->confirm($orderId, $data['token'])->get_data();
        $this->assertSame(['paid', $orderId, 0], [$paid['status'], $paid['order_id'], $paid['cart']['count']]);
        $this->assertSame(['paid', 'succeeded'], [(new \PPCart_Order($orderId))->status, (new \PPCart_Order($orderId))->payment_status]);
        $this->assertSame(1, $this->completed);
        $this->assertTrue($this->store()->read()->is_empty());

        $this->assertSame('paid', $this->confirm($orderId, $data['token'])->get_data()['status']);
        $this->assertSame(1, $this->completed);

        $buyNow = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_status' => 'pending-payment']);
        $this->assertSame('ppcart_cart_order_missing', $this->confirm($buyNow, \PPCart_Order::ensure_invoice_access_token($buyNow))->get_error_code());
    }

    /** @test-id IT-453 */
    public function test_IT_453_a_cart_that_changed_after_the_form_loaded_is_refused_before_any_order_or_payment(): void
    {
        $seen = $this->cart()->revision;
        update_post_meta($this->toolkit, '_ppcart_pay_options', [$this->plan('personal', '35')]);

        $refused = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $seen]);
        $this->assertSame(['ppcart_cart_changed', 409], [$refused->get_error_code(), $refused->get_error_data()['status']]);
        $cart = $refused->get_error_data()['cart'];
        $this->assertSame(['94.00', 'price_changed', '29.00', '35.00'], [$cart['totals']['total'], $cart['notices'][0]['code'], $cart['notices'][0]['old_price'], $cart['notices'][0]['new_price']]);
        $this->assertStringContainsString('data-ppcart-amount="9400"', $cart['fragments']['checkout']);
        $this->assertSame([0, []], [$this->orderCount(), $this->stripe->created]);

        $stale = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $seen]);
        $this->assertSame('ppcart_cart_changed', $stale->get_error_code());
        $this->assertSame(0, $this->orderCount());

        $accepted = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $cart['revision']])->get_data();
        $this->assertSame(9400, $accepted['payment']['amount']);
        $this->assertSame(94.0, (float) (new \PPCart_Order($accepted['order_id']))->amount);

        $twice = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $cart['revision']]);
        $this->assertSame('ppcart_cart_changed', $twice->get_error_code());
        $this->assertSame([1, 1], [$this->orderCount(), count($this->stripe->created)]);
    }

    /** @test-id IT-453 */
    public function test_IT_453_invalid_input_limits_and_gateway_errors_never_charge(): void
    {
        update_option('_ppcart_cart_checkout_terms', 1);
        update_option('_ppcart_cart_checkout_terms_url', 'https://example.org/terms');
        $seen = $this->cart()->revision;

        $invalid = $this->checkout(['email' => 'not-an-email', 'first_name' => ' ', 'pay-method' => 'stripe', 'ppcart_cart_revision' => $seen]);
        $this->assertSame(['ppcart_cart_checkout_invalid', 422, ['first_name', 'last_name', 'email', 'terms']], [$invalid->get_error_code(), $invalid->get_error_data()['status'], $invalid->get_error_data()['fields']]);
        $buyer = self::BUYER + ['terms' => '1', 'ppcart_cart_revision' => $seen];
        $this->assertSame('ppcart_cart_payment_method', $this->checkout(['pay-method' => 'cod'] + $buyer)->get_error_code());
        $this->assertSame([0, $seen], [$this->orderCount(), $this->store()->read()->revision]);

        update_post_meta($this->course, '_ppcart_customer_purchase_limit', '1');
        $earlier = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_status' => 'paid']);
        update_post_meta($earlier, '_ppcart_email', 'buyer@example.org');
        update_post_meta($earlier, '_ppcart_product_id', $this->course);
        $limited = $this->checkout($buyer);
        $this->assertSame(['ppcart_cart_purchase_limit', 422], [$limited->get_error_code(), $limited->get_error_data()['status']]);
        $this->assertSame($limited->get_error_data()['key'], $this->course . ':team');
        $this->assertSame([1, [], $seen], [$this->orderCount(), $this->stripe->created, $this->store()->read()->revision]);
        delete_post_meta($this->course, '_ppcart_customer_purchase_limit');

        $this->stripe->error = 'Your card was declined by the test gateway.';
        $failed = $this->checkout($buyer);
        $this->assertSame(['ppcart_cart_payment_failed', 402, 'Your card was declined by the test gateway.'], [$failed->get_error_code(), $failed->get_error_data()['status'], $failed->get_error_message()]);
        $this->assertSame([2, $seen + 1], [$failed->get_error_data()['cart']['count'], $failed->get_error_data()['cart']['revision']]);
        $order = new \PPCart_Order(get_posts(['post_type' => ppcart_live_post_type('order'), 'post_status' => 'pending-payment', 'fields' => 'ids'])[0]);
        $this->assertSame(['pending-payment', 'accepted', 0], [$order->status, $order->accept_terms, $this->completed]);
        $this->assertNull($order->transaction_id);
    }

    /** @test-id IT-453 */
    public function test_IT_453_free_cart_and_cash_on_delivery_need_no_gateway(): void
    {
        $sample = $this->product('Free Sample', [$this->plan('gift', '0', 'free')]);
        $free = $this->checkout(['pay-method' => ''] + self::BUYER + ['ppcart_cart_revision' => $this->add($sample, 'gift')->revision])->get_data();
        $order = new \PPCart_Order($free['order_id']);
        $this->assertSame(['paid', 'paid', 'free', 0.0, 0], [$free['status'], $order->status, $order->pay_method, (float) $order->amount, $free['cart']['count']]);
        $this->assertSame([[], 1], [$this->stripe->created, $this->completed]);

        update_option('_ppcart_cashondelivery_enable', '1');
        $this->assertSame(['stripe', 'cod'], array_keys($this->controller()->payment_methods()));
        $noMethod = $this->checkout(['pay-method' => ''] + self::BUYER + ['ppcart_cart_revision' => $this->add($this->toolkit)->revision]);
        $this->assertSame(['ppcart_cart_payment_method', 422, 0], [$noMethod->get_error_code(), $noMethod->get_error_data()['status'], $this->orderCount() - 1]);
        $this->asGuestWith();
        $cod = $this->checkout(['pay-method' => 'cod'] + self::BUYER + ['ppcart_cart_revision' => $this->cart()->revision])->get_data();
        $order = new \PPCart_Order($cod['order_id']);
        $this->assertSame(['pending', 'pending-payment', 'cod', 88.0, 0], [$cod['status'], $order->status, $order->pay_method, (float) $order->amount, $cod['cart']['count']]);
        $this->assertSame([], $this->stripe->created);
    }

    /** @test-id IT-453 */
    public function test_IT_453_routes_need_a_nonce_and_an_enabled_cart(): void
    {
        $dispatch = static function (bool $nonce): \WP_REST_Response {
            $request = new \WP_REST_Request('POST', '/ppcart/v1/cart/checkout');
            if ($nonce) {
                $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
            }
            return rest_get_server()->dispatch($request);
        };
        $this->assertSame([403, 'rest_cookie_invalid_nonce'], [$dispatch(false)->get_status(), $dispatch(false)->get_data()['code']]);
        $this->assertSame([422, 'ppcart_cart_checkout_invalid'], [$dispatch(true)->get_status(), $dispatch(true)->get_data()['code']]);
        update_option('_ppcart_shopping_cart_enable', 0);
        $this->assertSame([403, 'ppcart_cart_disabled'], [$dispatch(true)->get_status(), $dispatch(true)->get_data()['code']]);
        $this->assertSame('', ppcart_render_shopping_cart_checkout());

        $confirm = new \WP_REST_Request('POST', '/ppcart/v1/cart/checkout/confirm');
        $confirm->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $this->assertSame(400, rest_get_server()->dispatch($confirm)->get_status());
    }

    /** @test-id IT-454 */
    public function test_IT_454_checkout_block_with_source_cart_and_the_shortcode_render_the_cart_form(): void
    {
        update_option('_ppcart_cart_checkout_phone', 1);
        update_option('_ppcart_cart_page_id', self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']));
        $cart = $this->cart();
        $productPage = get_post($this->toolkit);
        $GLOBALS['post'] = $productPage;
        setup_postdata($productPage);

        $block = do_blocks('<!-- wp:publishpress-cart/checkout-form {"pid":"cart","styleSettings":{"accentColor":"#225588"}} /-->');
        wp_reset_postdata();
        $this->assertStringContainsString('data-ppcart-checkout-form', $block);
        $this->assertStringContainsString('name="ppcart_cart_revision" value="' . $cart->revision . '"', $block);
        $this->assertStringContainsString('data-ppcart-stripe-key="pk_test_cart"', $block);
        $this->assertStringContainsString('data-ppcart-amount="8800"', $block);
        $this->assertStringContainsString('--ppcart-accent:#225588', $block);
        foreach (['name="first_name"', 'name="last_name"', 'name="email"', 'name="phone"', 'Writer Toolkit', 'Editing Course', '88.00', 'Edit cart'] as $expected) {
            $this->assertStringContainsString($expected, $block);
        }
        foreach (['ppcart_product_id', 'ppcart_product_option', 'ppcart-orderbump', 'coupon', 'name="address1"', 'name="terms"'] as $absent) {
            $this->assertStringNotContainsString($absent, $block);
        }
        $this->assertTrue(defined('DONOTCACHEPAGE'));

        $shortcode = do_shortcode('[ppcart_cart_checkout]');
        $this->assertStringContainsString('name="ppcart_cart_revision" value="' . $cart->revision . '"', $shortcode);
        $this->assertStringContainsString('data-ppcart-amount="8800"', $shortcode);

        $this->asGuestWith();
        $this->assertStringContainsString('Your cart is empty.', do_shortcode('[ppcart_cart_checkout]'));
        $this->assertStringNotContainsString('data-ppcart-checkout-form', do_shortcode('[ppcart_cart_checkout]'));
    }

    /** @test-id IT-454 */
    public function test_IT_454_confirmation_shows_the_cart_settings_message_and_every_item_to_the_buyer_only(): void
    {
        update_option('_ppcart_cart_confirmation_message', 'Thanks {customer_firstname}, your files are ready.');
        $data = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $this->cart()->revision])->get_data();
        $this->asGuestWith();

        $_GET = ['ppcart-order' => (string) $data['order_id'], 'token' => 'wrong'];
        $this->assertStringContainsString('Your cart is empty.', ppcart_render_shopping_cart_checkout());

        $_GET['token'] = $data['token'];
        $pending = ppcart_render_shopping_cart_checkout();
        $this->assertStringContainsString('data-ppcart-pending-order="' . $data['order_id'] . '"', $pending);
        $this->assertStringNotContainsString('Thanks Ada', $pending);

        $this->stripe->status = 'succeeded';
        $this->confirm($data['order_id'], $data['token']);
        $paid = ppcart_render_shopping_cart_checkout();
        $this->assertStringContainsString('Thanks Ada, your files are ready.', $paid);
        $this->assertStringNotContainsString('data-ppcart-pending-order', $paid);
        foreach (['Writer Toolkit', 'Editing Course', '29.00', '59.00', '88.00'] as $expected) {
            $this->assertStringContainsString($expected, $paid);
        }
    }

    /** @test-id IT-454 */
    public function test_IT_454_a_payment_that_left_the_page_returns_to_the_checkout_page_before_the_confirmation(): void
    {
        update_option('_ppcart_cart_confirmation', 'url');
        update_option('_ppcart_cart_redirect', 'https://merchant.example/thanks');
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $data = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $this->cart()->revision, 'ppcart_page_id' => $page])->get_data();
        $orderId = $data['order_id'];
        $checkoutPage = \PPCart_Order::confirmation_url(get_permalink($page), $orderId);
        $this->asGuestWith();

        $this->assertSame('https://merchant.example/thanks', $data['confirmation']['url']);
        $this->assertSame($checkoutPage, $data['payment']['return_url']);

        $_GET = ['ppcart-order' => (string) $orderId, 'token' => $data['token'], 'payment_intent' => 'pi_cart_1'];
        $this->assertSame('', $this->controller()->payment_return_url());

        $this->stripe->status = 'succeeded';
        $_GET['token'] = 'wrong';
        $this->assertSame('', $this->controller()->payment_return_url());
        $_GET = ['ppcart-order' => (string) $orderId, 'token' => $data['token']];
        $this->assertSame('', $this->controller()->payment_return_url());
        $this->assertSame(['pending-payment', 0], [(new \PPCart_Order($orderId))->status, $this->completed]);

        $_GET['payment_intent'] = 'pi_cart_1';
        $this->assertSame('https://merchant.example/thanks', $this->controller()->payment_return_url());
        $this->assertSame(['paid', 1], [(new \PPCart_Order($orderId))->status, $this->completed]);

        update_option('_ppcart_cart_confirmation', 'message');
        $this->assertSame($checkoutPage, $this->controller()->payment_return_url());
        $this->assertSame(1, $this->completed);
    }

    /** @test-id IT-454 */
    public function test_IT_454_editor_preview_of_source_cart_needs_the_product_capability(): void
    {
        $preview = static function (): \WP_REST_Response {
            $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/checkout-block/preview');
            $request->set_query_params(['pid' => 'cart', 'style_settings' => '{"accentColor":"#225588"}']);
            return rest_get_server()->dispatch($request);
        };
        $this->assertContains($preview()->get_status(), [401, 403]);

        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-activator.php';
        \PPCart_Activator::add_cap();
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $data = $preview()->get_data();
        $this->assertSame(200, $preview()->get_status());
        $this->assertSame('Cart', $data['title']);
        $this->assertStringContainsString('data-ppcart-checkout-form', $data['html']);
        $this->assertStringContainsString('--ppcart-accent:#225588', $data['html']);
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*disabled/', $data['html']);
    }

    /** @test-id IT-455 */
    public function test_IT_455_stripe_charge_settles_a_cart_order_only_through_the_payment_the_order_stored(): void
    {
        $data = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $this->cart()->revision])->get_data();
        $orderId = $data['order_id'];
        $charge = static fn (string $intent): object => (object) [
            'id' => 'ch_' . $intent, 'payment_intent' => $intent, 'amount' => 8800, 'currency' => 'usd', 'refunded' => false,
            'metadata' => (object) ['ppcart_checkout_type' => 'cart', 'ppcart_order_id' => $orderId, 'origin' => get_site_url()],
        ];

        $this->assertFalse(\PPCart_Stripe_Sync::sync_charge_status($charge('pi_of_another_order')));
        $this->assertSame(['pending-payment', 0], [(new \PPCart_Order($orderId))->status, $this->completed]);

        $synced = \PPCart_Stripe_Sync::sync_charge_status($charge('pi_cart_1'));
        $this->assertSame($orderId, (int) $synced->id);
        $this->assertSame(['paid', 'ch_pi_cart_1', 1], [(new \PPCart_Order($orderId))->status, (new \PPCart_Order($orderId))->transaction_id, $this->completed]);
        $this->assertTrue($this->store()->read()->is_empty());
        $this->assertSame('paid', $this->confirm($orderId, $data['token'])->get_data()['status']);
        $this->assertSame(1, $this->completed);
    }

    /** @test-id IT-455 */
    public function test_IT_455_buy_now_endpoints_do_not_settle_or_reveal_a_cart_order(): void
    {
        $data = $this->checkout(self::BUYER + ['ppcart_cart_revision' => $this->cart()->revision])->get_data();
        $orderId = $data['order_id'];
        $_POST = ['ppcart-nonce' => wp_create_nonce('ppcart_purchase_nonce'), 'response' => ['order_id' => (string) $orderId]];

        $die = static fn (): \Closure => static function (): void {
            throw new \WPDieException('');
        };
        add_filter('wp_die_ajax_handler', $die, 999);
        add_filter('wp_die_handler', $die, 999);
        add_filter('wp_doing_ajax', '__return_true');
        ob_start();
        try {
            (new \PPCart_Public_Order_Controller())->update_stripe_order_status();
        } catch (\WPDieException $e) {
            unset($e);
        } finally {
            $output = (string) ob_get_clean();
            remove_filter('wp_die_ajax_handler', $die, 999);
            remove_filter('wp_die_handler', $die, 999);
            remove_filter('wp_doing_ajax', '__return_true');
        }

        $this->assertSame(['success' => false, 'data' => ['error' => 'Invalid Request']], json_decode($output, true));
        $this->assertStringNotContainsString($data['token'], $output);
        $this->assertSame(['pending-payment', 0], [(new \PPCart_Order($orderId))->status, $this->completed]);
    }
}
