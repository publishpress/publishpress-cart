<?php

declare(strict_types=1);

namespace Tests\Integration\ShoppingCart;

use Tests\Support\Integration\ShoppingCartTestCase;

class ShoppingCartPaypalTest extends ShoppingCartTestCase
{
    private array $requests = [];
    private array $remote = [];
    private ?array $capture = null;
    private bool $minimalCapture = false;
    private array $pdt = [];
    private array $products = [];
    private array $mail = [];
    private int $completed = 0;
    private int $checkoutPage;
    private mixed $currency;
    private array $post;
    private mixed $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currency = $GLOBALS['ppcart_currency'] ?? null;
        $this->post = $_POST;
        $this->product = $GLOBALS['ppcart_product'] ?? null;
        $GLOBALS['ppcart_currency'] = 'USD';
        update_option('_ppcart_currency', 'USD');
        update_option('_ppcart_shopping_cart_enable', 1);
        update_option('_ppcart_paypal_enable', 1);
        update_option('_ppcart_paypal_enable_sandbox', 'enable');
        update_option('_ppcart_paypal_sandbox_email', 'merchant@example.org');
        update_option('_ppcart_paypal_sandbox_client_id', 'shopping-cart-test-client');
        ppcart_set_sensitive_option('_ppcart_paypal_sandbox_secret', 'test-secret');
        update_option(\PPCart_Paypal_Onpage::OPTION_EXPERIENCE, 'onpage');
        $this->checkoutPage = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        update_option('_ppcart_cart_checkout_page_id', $this->checkoutPage);
        $this->products = [$this->product('Writer Toolkit'), $this->product('Editing Course')];
        foreach ($this->products as $id) {
            update_post_meta($id, '_ppcart_manage_stock', '1');
            update_post_meta($id, '_ppcart_limit', '5');
            update_post_meta($id, '_ppcart_files', [['file_id' => 'file-' . $id, 'file_name' => 'Download', 'file_url' => 'https://example.org/file.pdf']]);
        }
        update_option('_ppcart_email_confirmation_enable', '1');
        update_option('_ppcart_email_confirmation_subject', 'Receipt {order_inline_list}');
        update_option('_ppcart_email_confirmation_body', '{order_list}');
        add_action('ppcart_order_complete', function (): void { $this->completed++; });
        add_filter('pre_wp_mail', function ($result, array $mail): bool {
            if ('buyer@example.org' === $mail['to']) $this->mail[] = $mail;
            return true;
        }, 10, 2);
        add_filter('pre_http_request', [$this, 'paypalHttp'], 10, 3);
    }

    protected function tearDown(): void
    {
        remove_filter('pre_http_request', [$this, 'paypalHttp'], 10);
        $GLOBALS['ppcart_currency'] = $this->currency;
        $GLOBALS['ppcart_product'] = $this->product;
        $_POST = $this->post;
        parent::tearDown();
    }

    public function paypalHttp($pre, array $args, string $url)
    {
        if (! str_contains($url, 'paypal.com/')) return $pre;
        $path = wp_parse_url($url, PHP_URL_PATH);
        $body = is_array($args['body'] ?? null) ? $args['body'] : json_decode((string) ($args['body'] ?? ''), true);
        $this->requests[] = [$args['method'], $path, $body];
        $response = [];
        if ('/v1/oauth2/token' === $path) {
            $response = ['access_token' => 'test-oauth-token', 'expires_in' => 3600];
        } elseif ('/v2/checkout/orders' === $path) {
            $id = 'PAYPAL-' . count($this->remote);
            $this->remote[$id] = ['id' => $id, 'status' => 'CREATED', 'purchase_units' => $body['purchase_units']];
            $response = $this->remote[$id];
        } elseif (preg_match('#^/v2/checkout/orders/([^/]+)(/capture)?$#', $path, $matches)) {
            $response = $this->remote[$matches[1]];
            if (! empty($matches[2])) {
                $response = $this->capture ?? $this->paidResource($response);
                $this->remote[$matches[1]] = $response;
                if ($this->minimalCapture) $response = ['id' => $response['id'], 'status' => 'COMPLETED'];
            }
        } elseif ('/v2/payments/refunds/REFUND-1' === $path) {
            $response = ['id' => 'REFUND-1', 'status' => 'COMPLETED', 'amount' => ['value' => '58.00', 'currency_code' => 'USD'], 'links' => [['rel' => 'up', 'href' => 'https://api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE-1']]];
        } elseif ('/v2/payments/captures/CAPTURE-1' === $path) {
            $response = ['id' => 'CAPTURE-1', 'status' => 'REFUNDED'];
        } elseif ('/cgi-bin/webscr' === $path) {
            $lines = ['SUCCESS'];
            foreach ($this->pdt as $key => $value) $lines[] = $key . '=' . rawurlencode((string) $value);
            return ['response' => ['code' => 200], 'body' => implode("\n", $lines)];
        } else {
            return new \WP_Error('test_unexpected_paypal_request', $path);
        }
        return ['response' => ['code' => 200], 'body' => wp_json_encode($response)];
    }

    private function paidResource(array $resource): array
    {
        $resource['status'] = 'COMPLETED';
        $resource['purchase_units'][0]['payments']['captures'] = [['id' => 'CAPTURE-1', 'status' => 'COMPLETED', 'amount' => $resource['purchase_units'][0]['amount']]];
        return $resource;
    }

    private function checkout(): array
    {
        foreach ($this->products as $id) $cart = $this->add($id);
        $request = new \WP_REST_Request('POST', '/ppcart/v1/cart/checkout');
        $request->set_body_params(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'buyer@example.org', 'pay-method' => 'paypal', 'ppcart_cart_revision' => $cart->revision]);
        $response = (new \PPCart_Public_Cart_Checkout_Controller())->checkout($request);
        $this->assertNotWPError($response);
        return $response->get_data();
    }

    private function confirm(array $checkout)
    {
        $request = new \WP_REST_Request('POST', '/ppcart/v1/cart/checkout/confirm');
        $request->set_body_params(['order_id' => $checkout['order_id'], 'token' => $checkout['token'], 'status' => 'COMPLETED', 'payment_status' => 'Completed']);
        return (new \PPCart_Public_Cart_Checkout_Controller())->confirm($request);
    }

    private function assertPaidOnce(int $id): void
    {
        $order = new \PPCart_Order($id);
        $this->assertSame(['cart', 'paid', 'paypal', 'CAPTURE-1', 58.0], [$order->checkout_type, $order->status, $order->pay_method, $order->transaction_id, (float) $order->amount]);
        $this->assertFalse(metadata_exists('post', $id, '_ppcart_product_id'));
        $this->assertCount(2, $order->get_items());
        $this->assertSame([1, 1, 0], [$this->completed, count($this->mail), $this->store()->read()->count()]);
        foreach ($this->products as $product) {
            $this->assertSame('4', get_post_meta($product, '_ppcart_limit', true));
            $this->assertStringContainsString(get_the_title($product), $this->mail[0]['message']);
        }
        $db = $GLOBALS['wpdb'];
        $downloads = $db->get_col($db->prepare('SELECT product_id FROM %i WHERE order_id = %d ORDER BY download_id', ppcart_live_table('downloads'), $id));
        $this->assertSame($this->products, array_map('intval', $downloads));
    }

    /** @test-id IT-448 */
    public function test_IT_448_paypal_creates_one_purchase_unit_for_the_stored_order_and_uses_cart_urls(): void
    {
        $checkout = $this->checkout();
        $order = new \PPCart_Order($checkout['order_id']);
        $this->assertSame(['requires_approval', 'pending-payment', 'test', 58.0], [$checkout['status'], $order->status, $order->gateway_mode, (float) $order->amount]);
        $this->assertFalse(metadata_exists('post', $order->id, '_ppcart_product_id'));
        $creates = array_values(array_filter($this->requests, static fn ($request) => '/v2/checkout/orders' === $request[1]));
        $this->assertCount(1, $creates);
        $body = $creates[0][2];
        $this->assertSame([['custom_id' => 'ppcart:' . $order->id, 'amount' => ['currency_code' => 'USD', 'value' => '58.00'], 'description' => $order->item_name]], $body['purchase_units']);
        $context = $body['payment_source']['paypal']['experience_context'];
        $this->assertSame(get_permalink($this->checkoutPage), $context['cancel_url']);
        $this->assertStringContainsString('ppcart-order=' . $order->id, $context['return_url']);
        $this->assertStringContainsString('ppcart-access=', $context['return_url']);
        $this->assertStringNotContainsString('token=', $context['return_url']);
        $this->assertTrue(\PPCart_Paypal_Sync::is_onpage_order($order->id));
        $this->assertSame($checkout['paypal']['orderId'], ppcart_get_post_meta($order->id, 'paypal_order_id'));
    }

    /** @test-id IT-448 */
    public function test_IT_448_forged_browser_status_and_wrong_order_currency_total_or_capture_never_pay(): void
    {
        $checkout = $this->checkout();
        $id = $checkout['paypal']['orderId'];
        $original = $this->remote[$id];
        $this->assertSame('ppcart_cart_payment_incomplete', $this->confirm($checkout)->get_error_code());
        foreach (['custom_id' => 'ppcart:999999', 'amount' => ['currency_code' => 'EUR', 'value' => '58.00'], 'amount2' => ['currency_code' => 'USD', 'value' => '0.01']] as $key => $value) {
            $this->remote[$id] = $this->paidResource($original);
            $this->remote[$id]['purchase_units'][0]['amount2' === $key ? 'amount' : $key] = $value;
            $this->assertSame('ppcart_cart_payment_incomplete', $this->confirm($checkout)->get_error_code());
        }
        $this->remote[$id] = $original;
        $this->remote[$id]['status'] = 'APPROVED';
        $this->capture = $this->paidResource($original);
        $this->capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] = '0.01';
        $this->assertSame('ppcart_cart_payment_incomplete', $this->confirm($checkout)->get_error_code());
        $this->assertSame(['pending-payment', 0, 0, 2], [(new \PPCart_Order($checkout['order_id']))->status, $this->completed, count($this->mail), $this->store()->read()->count()]);

        $this->remote[$id] = $original;
        $this->remote[$id]['status'] = 'APPROVED';
        $this->capture = null;
        $this->minimalCapture = true;
        $this->assertSame('paid', $this->confirm($checkout)->get_data()['status']);
        $this->confirm($checkout);
        $this->assertPaidOnce($checkout['order_id']);
    }

    /** @test-id IT-449 */
    public function test_IT_449_webhook_reconcile_and_refund_sync_handle_a_cart_order_without_product_meta(): void
    {
        $checkout = $this->checkout();
        $id = $checkout['paypal']['orderId'];
        $event = ['event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => ['status' => 'COMPLETED', 'supplementary_data' => ['related_ids' => ['order_id' => $id]]]];
        $this->assertSame(200, \PPCart_Paypal_Webhook::handle($event));
        $this->assertSame('pending-payment', (new \PPCart_Order($checkout['order_id']))->status);
        $this->remote[$id]['status'] = 'APPROVED';
        $onpage = new \PPCart_Paypal_Onpage(new \PPCart_Paypal('', '', false), 'test');
        $onpage->reconcile();
        $this->assertPaidOnce($checkout['order_id']);
        delete_transient('ppcart_pp_wh_' . md5($id));
        $this->assertSame(200, \PPCart_Paypal_Webhook::handle($event));
        $this->assertPaidOnce($checkout['order_id']);
        $refund = new \PPCart_Paypal_Refund_Sync();
        $this->assertSame(\PPCart_Paypal_Sync::RESULT_COMPLETE, $refund->sync_refund_capture('REFUND-1'));
        $this->assertSame(\PPCart_Paypal_Sync::RESULT_COMPLETE, $refund->sync_refund_capture('REFUND-1'));
        $order = new \PPCart_Order($checkout['order_id']);
        $this->assertSame('refunded', $order->status);
        $this->assertCount(1, $order->refund_log);
        foreach ($this->products as $product) $this->assertSame('4', get_post_meta($product, '_ppcart_limit', true));
        $this->assertCount(1, $this->mail);
    }

    /** @test-id IT-449 */
    public function test_IT_449_webhook_reads_the_paid_capture_before_completing_the_cart_order(): void
    {
        $checkout = $this->checkout();
        $id = $checkout['paypal']['orderId'];
        $this->remote[$id] = $this->paidResource($this->remote[$id]);
        $event = ['event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => ['supplementary_data' => ['related_ids' => ['order_id' => $id]]]];
        $this->assertSame(200, \PPCart_Paypal_Webhook::handle($event));
        $this->assertPaidOnce($checkout['order_id']);
    }

    /** @test-id IT-448 */
    public function test_IT_448_onpage_approval_accepts_a_cart_order_token_and_ignores_browser_status(): void
    {
        $checkout = $this->checkout();
        $approve = function (string $token) use ($checkout): array {
            $onpage = new \PPCart_Paypal_Onpage(new \PPCart_Paypal('', '', false), 'test');
            $_POST = ['ppcart-nonce' => wp_create_nonce('ppcart_purchase_nonce'), 'ppcart_order_id' => $checkout['order_id'], 'access' => $token, 'status' => 'COMPLETED'];
            $die = static fn (): \Closure => static function (): void { throw new \WPDieException(''); };
            add_filter('wp_die_ajax_handler', $die, 999);
            add_filter('wp_die_handler', $die, 999);
            add_filter('wp_doing_ajax', '__return_true');
            ob_start();
            try {
                $onpage->ajax_approve();
            } catch (\WPDieException $error) {
                unset($error);
            } finally {
                $output = ob_get_clean();
                remove_filter('wp_die_ajax_handler', $die, 999);
                remove_filter('wp_die_handler', $die, 999);
                remove_filter('wp_doing_ajax', '__return_true');
            }
            return json_decode($output, true);
        };
        $this->assertArrayHasKey('error', $approve('wrong-token'));
        $this->assertSame(['retry' => true], $approve($checkout['token']));
        $this->assertSame('pending-payment', (new \PPCart_Order($checkout['order_id']))->status);
        $id = $checkout['paypal']['orderId'];
        $this->remote[$id]['status'] = 'APPROVED';
        $this->assertStringContainsString('ppcart-order=' . $checkout['order_id'], $approve($checkout['token'])['url']);
        $this->assertPaidOnce($checkout['order_id']);
    }

    /** @test-id IT-450 */
    public function test_IT_450_standard_uses_order_name_cart_urls_and_verifies_the_pdt_payment(): void
    {
        update_option(\PPCart_Paypal_Onpage::OPTION_EXPERIENCE, 'redirect');
        update_option('_ppcart_cart_confirmation', 'url');
        update_option('_ppcart_cart_redirect', 'https://example.org/cart-thanks');
        $checkout = $this->checkout();
        $order = new \PPCart_Order($checkout['order_id']);
        parse_str(wp_parse_url($checkout['url'], PHP_URL_QUERY), $vars);
        $this->assertSame(['redirect', $order->item_name, '58', get_permalink($this->checkoutPage)], [$checkout['status'], $vars['item_name'], $vars['amount'], $vars['cancel_return']]);
        $this->assertStringStartsWith('https://example.org/cart-thanks?', $vars['return']);
        $this->assertArrayNotHasKey('item_number', $vars);
        $this->assertFalse(\PPCart_Paypal_Sync::is_onpage_order($order->id));
        $this->assertSame('ppcart_cart_payment_incomplete', $this->confirm($checkout)->get_error_code());

        $this->pdt = ['custom' => wp_json_encode(['order_id' => $order->id]), 'mc_currency' => 'USD', 'mc_gross' => '58.00', 'receiver_email' => 'merchant@example.org', 'txn_id' => 'CAPTURE-1', 'payment_status' => 'Completed'];
        $pdt = new \ReflectionMethod(\PPCart_Paypal::class, 'run_pdt_check');
        $paypal = new \PPCart_Paypal('', '', false);
        $check = fn () => $pdt->invoke($paypal, $order->get_data(), ['paypalUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr', 'tx' => 'CAPTURE-1', 'paypalPDT' => 'test-pdt-token']);
        foreach (['custom' => wp_json_encode(['order_id' => $order->id + 1]), 'mc_gross' => '0.01', 'mc_currency' => 'EUR', 'receiver_email' => 'another@example.org'] as $key => $wrong) {
            $good = $this->pdt[$key];
            $this->pdt[$key] = $wrong;
            $this->assertFalse($check());
            $this->assertSame('pending-payment', (new \PPCart_Order($order->id))->status);
            $this->pdt[$key] = $good;
        }
        $this->assertTrue($check());
        $this->assertPaidOnce($order->id);
    }

    /** @test-id IT-450 */
    public function test_IT_450_buy_now_paypal_keeps_its_product_and_return_urls(): void
    {
        $product = $this->products[0];
        $GLOBALS['ppcart_product'] = (object) ['thanks_url' => 'https://example.org/product-thanks', 'confirmation' => 'redirect'];
        $order = new \PPCart_Order();
        $order->product_id = $product;
        $order->product_name = 'Writer Toolkit';
        $order->amount = 29;
        $order->pay_method = 'paypal';
        $paypal = new \PPCart_Paypal('', '', false);
        $paypal->paypal_store_checkout_order($order, null, $product, 'https://example.org/product-cancel');
        $this->assertSame('https://example.org/product-cancel', $order->cancel_url);
        $this->assertStringStartsWith('https://example.org/product-thanks?', $order->return_url);
        $this->assertStringContainsString('ppcart-pid=' . $product, $order->return_url);
        $this->assertSame((string) $product, get_post_meta($order->id, '_ppcart_product_id', true));
        $this->assertFalse(metadata_exists('post', $order->id, '_ppcart_checkout_type'));
    }
}
