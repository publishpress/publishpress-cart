<?php

declare(strict_types=1);
namespace Tests\Integration\Mcp;
use Tests\Support\Integration\McpTestCase;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;
require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

/** Permanent behavior regressions for the MCP security boundaries. */
class SecurityRegressionTest extends McpTestCase
{
    // Intercepted HTTP fixtures use reserved documentation IPs, avoiding external DNS dependencies.
    public function test_explicit_read_post_denial_blocks_record_disclosure(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'draft',
            'post_title' => 'Confidential audit title', 'post_content' => 'audit-private-content-sentinel']);
        $deny = static function ($caps, $cap, $user, $args) use ($id) {
            return 'read_post' === $cap && (int) ($args[0] ?? 0) === $id ? ['do_not_allow'] : $caps;
        };
        add_filter('map_meta_cap', $deny, 10, 4);
        try {
            $this->assertFalse(current_user_can('read_post', $id));
            $this->assertTrue(current_user_can('edit_post', $id));
            $read = $this->execute('get-record', ['kind' => 'product', 'id' => $id]);
            $this->assertWPError($read);
            $this->assertWPError($this->execute('get-product-settings', ['id' => $id]));
        } finally {
            remove_filter('map_meta_cap', $deny, 10);
        }
    }

    public function test_password_preview_suspicion_is_blocked_by_row_validation(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Requires Pro custom fields.'); }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        ppcart_update_post_meta($id, 'custom_fields', [['field_id' => 'password', 'field_label' => 'Password',
            'field_type' => 'password', 'default_value' => 'audit-private-password-sentinel']]);
        $read = $this->execute('get-custom-fields', ['id' => $id]);
        $this->assertNotWPError($read);
        $this->assertStringNotContainsString('audit-private-password-sentinel', wp_json_encode($read));
        $preview = $this->execute('set-custom-fields', ['id' => $id, 'revision' => $read['revision'], 'dry_run' => true,
            'rows' => [(object) ['field_id' => 'password', 'field_label' => 'Renamed password']]]);
        $this->assertWPError($preview);
        $this->assertStringNotContainsString('audit-private-password-sentinel', wp_json_encode($preview));
    }

    public function test_uncertain_refund_cannot_repeat_the_remote_request(): void
    {
        update_option('_ppcart_currency', 'USD');
        update_option('_ppcart_stripe_test_sk', 'sk_test_synthetic_audit');
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        foreach (['status' => 'paid', 'payment_status' => 'paid', 'pay_method' => 'stripe', 'gateway_mode' => 'test',
            'transaction_id' => 'ch_synthetic_audit', 'amount' => '20', 'currency' => 'USD'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        $http = new class implements ClientInterface {
            public $calls = 0;
            public $urls = [];
            public $params = [];
            public function request($method, $url, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                ++$this->calls;
                $this->urls[] = $url;
                $this->params[] = $params;
                // Models a provider accepting the POST but its response being lost.
                throw new \RuntimeException('Synthetic lost response after remote acceptance.');
            }
        };
        ApiRequestor::setHttpClient($http);
        try {
            $read = $this->execute('get-record', ['kind' => 'order', 'id' => $id]);
            $request = ['id' => $id, 'revision' => $read['revision'], 'amount' => '5.00'];
            $first = $this->execute('refund-order', $request);
            $this->assertWPError($first);
            $this->assertSame('ppcart_mcp_payment_unconfirmed', $first->get_error_code());
            $this->assertNotFalse(get_option('_ppcart_mcp_payment_lock_' . $id));
            $this->assertSame($read['revision'], $this->execute('get-record', ['kind' => 'order', 'id' => $id])['revision']);
            $this->assertWPError($this->execute('refund-order', $request));
            $this->assertSame(1, $http->calls);
            $this->assertStringContainsString('/refunds', $http->urls[0]);

        } finally {
            ApiRequestor::setHttpClient(null);
        }
    }
    public function test_custom_webhook_mapping_sends_only_selected_fields(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Requires Pro native webhook handler.'); }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        ppcart_update_post_meta($id, 'custom_fields', [['field_id' => 'expertise', 'field_label' => 'Expertise', 'field_type' => 'text']]);
        $read = $this->execute('get-product-integrations', ['id' => $id]);
        $this->assertNotWPError($read);
        $saved = $this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'], 'rows' => [(object) [
            'services' => 'webhook', 'service_trigger' => ['purchased'], 'webhook_method' => 'post', 'webhook_url' => 'https://203.0.113.10/merchant-hook',
            'field_map' => [(object) ['destination' => 'expertise', 'source' => 'custom.expertise']],
        ]]]);
        $this->assertNotWPError($saved);
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        foreach (['product_id' => $id, 'email' => 'synthetic-private@example.org', 'firstname' => 'Synthetic', 'lastname' => 'Customer',
            'address1' => 'Synthetic private address', 'status' => 'paid', 'ip_address' => '192.0.2.1',
            'custom_fields' => ['expertise' => ['label' => 'Expertise', 'value' => 'Publishing']]] as $key => $value) {
            ppcart_update_post_meta($order, $key, $value);
        }
        $sent = [];
        $http = static function ($result, $args, $url) use (&$sent) {
            $sent[] = json_decode($args['body'], true);
            return ['headers' => [], 'body' => '{}', 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []];
        };
        add_filter('pre_http_request', $http, 10, 3);
        try {
            $rules = ppcart_get_post_meta($id, 'integrations', true);
            \PPCart_Pro_Order_Behavior::custom_webhook_integration($rules[0], $id, (new \PPCart_Order($order))->get_data());
            $this->assertCount(1, $sent);
            $this->assertSame(['expertise' => 'Publishing'], $sent[0]);
        } finally {
            remove_filter('pre_http_request', $http, 10);
        }
    }

    public function test_webhook_authentication_cannot_cross_origins_or_follow_redirects(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Requires Pro native webhook handler.'); }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        ppcart_update_post_meta($id, 'integrations', [['services' => 'webhook', 'service_trigger' => ['purchased'],
            'webhook_url' => 'https://203.0.113.10/original-merchant', 'webhook_method' => 'post', 'hook_headers_on' => 1,
            'hook_headers' => 'Authorization:Bearer audit-sensitive-token', 'field_map' => 'email:customer_email']]);
        $read = $this->execute('get-product-integrations', ['id' => $id]);
        $this->assertNotWPError($read);
        $this->assertStringNotContainsString('audit-sensitive-token', wp_json_encode($read));
        $saved = $this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'],
            'rows' => [(object) ['row_id' => 0, 'webhook_url' => 'https://203.0.113.20/new-recipient']]]);
        $this->assertWPError($saved);
        $this->assertSame('https://203.0.113.10/original-merchant', ppcart_get_post_meta($id, 'integrations', true)[0]['webhook_url']);
        $same_origin = $this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'],
            'rows' => [(object) ['row_id' => 0, 'webhook_url' => 'https://203.0.113.10/new-path']]]);
        $this->assertNotWPError($same_origin);
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        ppcart_update_post_meta($order, 'product_id', $id);
        ppcart_update_post_meta($order, 'email', 'synthetic@example.org');
        $sent = [];
        $http = static function ($result, $args, $url) use (&$sent) {
            $sent[] = ['url' => $url, 'headers' => $args['headers'], 'redirection' => $args['redirection']];
            return ['headers' => [], 'body' => '{}', 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []];
        };
        add_filter('pre_http_request', $http, 10, 3);
        try {
            $rules = ppcart_get_post_meta($id, 'integrations', true);
            \PPCart_Pro_Order_Behavior::custom_webhook_integration($rules[0], $id, (new \PPCart_Order($order))->get_data());
            $this->assertSame('https://203.0.113.10/new-path', $sent[0]['url']);
            $this->assertSame(0, $sent[0]['redirection']);
            $this->assertSame('Bearer audit-sensitive-token', $sent[0]['headers']['Authorization']);
        } finally {
            remove_filter('pre_http_request', $http, 10);
        }
    }

}
