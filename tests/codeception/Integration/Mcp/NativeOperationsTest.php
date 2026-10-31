<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class NativeOperationsTest extends McpTestCase
{
    public function test_test_email_uses_synthetic_data_and_preview_never_sends(): void
    {
        update_option('_ppcart_email_confirmation_subject', 'Synthetic sample');
        update_option('_ppcart_email_confirmation_body', '<p>Hello {first_name}</p>');
        $sent = [];
        $capture = static function ($result, $args) use (&$sent) { $sent[] = $args; return true; };
        add_filter('pre_wp_mail', $capture, 10, 2);
        try {
            $preview = $this->execute('send-test-email', ['type' => 'confirmation', 'dry_run' => true]);
            $this->assertNotWPError($preview);
            $this->assertSame([], $sent);
            $saved = $this->execute('send-test-email', ['type' => 'confirmation']);
            $this->assertNotWPError($saved);
            $this->assertTrue($saved['sent']);
            $this->assertNotEmpty($sent);
            $this->assertStringContainsString('Synthetic sample', $sent[0]['subject']);
            $this->assertWPError($this->execute('send-test-email', ['type' => 'missing-template']));
        } finally {
            remove_filter('pre_wp_mail', $capture, 10);
        }
    }

    public function test_native_stripe_sync_preview_scope_staleness_and_mocked_refresh(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        foreach (['pay_method' => 'stripe', 'gateway_mode' => 'test', 'transaction_id' => 'ch_mcp_sync', 'product_id' => $product,
            'status' => 'pending-payment', 'payment_status' => 'pending-payment', 'currency' => 'USD', 'amount' => '20'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        update_option('_ppcart_stripe_test_sk', 'sk_test_synthetic');
        $http = new class($id, $product) implements ClientInterface {
            public $calls = 0;
            public $wrongOrder = 0;
            private $order;
            private $product;
            public function __construct($order, $product) { $this->order = $order; $this->product = $product; }
            public function request($method, $url, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                ++$this->calls;
                return [wp_json_encode(['id' => 'ch_mcp_sync', 'object' => 'charge', 'amount' => 2000, 'currency' => 'usd',
                    'refunded' => false, 'metadata' => ['ppcart_order_id' => $this->wrongOrder ?: $this->order, 'ppcart_product_id' => $this->product]]), 200, []];
            }
        };
        ApiRequestor::setHttpClient($http);
        add_filter('pre_wp_mail', '__return_true');
        try {
            $read = $this->execute('get-stripe-sync-scope', ['kind' => 'order', 'id' => $id]);
            $this->assertNotWPError($read);
            $this->assertStringNotContainsString('ch_mcp_sync', wp_json_encode($read));
            $request = ['kind' => 'order', 'id' => $id, 'revision' => $read['revision'], 'dry_run' => true];
            $this->assertNotWPError($this->execute('sync-stripe-record', $request));
            $this->assertSame(0, $http->calls);
            $request['dry_run'] = false;
            $result = $this->execute('sync-stripe-record', $request);
            $this->assertNotWPError($result);
            $this->assertGreaterThan(0, $http->calls);
            $this->assertSame('paid', ppcart_get_post_meta($id, 'status', true));
            $calls = $http->calls;
            $this->assertWPError($this->execute('sync-stripe-record', $request));
            $this->assertSame($calls, $http->calls);
            $foreign = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
            foreach (['pay_method' => 'stripe', 'gateway_mode' => 'test', 'transaction_id' => 'ch_foreign', 'status' => 'pending-payment'] as $key => $value) {
                ppcart_update_post_meta($foreign, $key, $value);
            }
            $http->wrongOrder = $foreign;
            ppcart_update_post_meta($id, 'status', 'pending-payment');
            $scope = $this->execute('get-stripe-sync-scope', ['kind' => 'order', 'id' => $id]);
            $this->assertNotWPError($scope);
            $request['revision'] = $scope['revision'];
            $this->assertWPError($this->execute('sync-stripe-record', $request));
            $this->assertSame('pending-payment', ppcart_get_post_meta($foreign, 'status', true));
            $this->assertSame('ch_foreign', ppcart_get_post_meta($foreign, 'transaction_id', true));

        } finally {
            ApiRequestor::setHttpClient(null);
            remove_filter('pre_wp_mail', '__return_true');
        }
    }
}
