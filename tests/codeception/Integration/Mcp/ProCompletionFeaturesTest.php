<?php

declare(strict_types=1);
namespace Tests\Integration\Mcp;
use Tests\Support\Integration\McpTestCase;
require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';
class ProCompletionFeaturesTest extends McpTestCase
{
    // Intercepted HTTP fixtures use reserved documentation IPs, avoiding external DNS dependencies.
    public function test_global_webhooks_preserve_other_subscriptions_and_minimize_delivery(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Requires Pro global subscriptions.'); }
        update_option('ppcart_zaphooks', []);
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
        $read = $this->execute('get-webhook-subscriptions', []);
        $this->assertNotWPError($read);
        $request = ['revision' => $read['revision'], 'changes' => (object) ['endpoint' => 'https://203.0.113.10/global-hook',
            'product_id' => $product, 'triggers' => ['purchased'], 'fields' => ['customer_email']], 'dry_run' => true];
        $this->assertNotWPError($this->execute('save-webhook-subscription', $request));
        $this->assertSame([], get_option('ppcart_zaphooks'));
        $request['dry_run'] = false;
        $saved = $this->execute('save-webhook-subscription', $request);
        $this->assertNotWPError($saved);
        $this->assertStringNotContainsString('https://203.0.113.10', wp_json_encode($saved));
        $id = $saved['subscriptions'][0]['id'];
        $before = get_option('ppcart_zaphooks');
        $this->assertWPError($this->execute('save-webhook-subscription', ['id' => $id, 'revision' => $saved['revision'],
            'changes' => (object) ['source' => 'Must not save', 'fields' => ['receipt_key']]]));
        $this->assertSame($before, get_option('ppcart_zaphooks'));
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        foreach (['product_id' => $product, 'status' => 'paid', 'email' => 'synthetic@example.org', 'address1' => 'Private unselected address', 'ip_address' => '192.0.2.1'] as $key => $value) { ppcart_update_post_meta($order, $key, $value); }
        $sent = [];
        $http = static function ($result, $args, $url) use (&$sent) { $sent[] = $args; return ['headers' => [], 'body' => '{}', 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []]; };
        add_filter('pre_http_request', $http, 10, 3);
        try {
            $native = (new \PPCart_Order($order))->get_data();
            $native['ID'] = $order;
            \PPCart_Pro_Order_Behavior::do_webhooks($native, $product, 'purchased');
            $this->assertCount(1, $sent);
            $this->assertSame(['customer_email' => 'synthetic@example.org'], json_decode($sent[0]['body'], true));
            $this->assertSame(0, $sent[0]['redirection']);
        } finally { remove_filter('pre_http_request', $http, 10); }
        $this->assertWPError($this->execute('delete-webhook-subscription', ['id' => $id, 'revision' => $read['revision']]));
        $deleted = $this->execute('delete-webhook-subscription', ['id' => $id, 'revision' => $saved['revision']]);
        $this->assertNotWPError($deleted);
        $this->assertSame([], get_option('ppcart_zaphooks'));
    }

    public function test_affiliate_analytics_check_contributors_and_exclude_visitor_identifiers(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Requires Pro affiliate analytics.'); }
        global $ppcart_pro_extensions, $wpdb;
        $this->assertNotWPError($ppcart_pro_extensions->set_control_state('affiliate', true));
        $affiliate = \PPCart_Pro_Affiliate::create_affiliate(['first_name' => 'Synthetic', 'last_name' => 'Affiliate', 'email' => 'analytics-' . wp_generate_password(8, false) . '@example.org', 'status' => 'pending', 'user_id' => 0]);
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $repository = new \PPCart_Pro_Affiliate_Repository();
        $repository->record_visit($affiliate, 'https://private.example/visitor', $product, 1, '192.0.2.4', 'https://private.example/referrer');
        $visits = $this->execute('list-affiliate-visits', ['affiliate_id' => $affiliate, 'per_page' => 1]);
        $this->assertNotWPError($visits);
        $this->assertCount(1, $visits['visits']);
        $this->assertStringNotContainsString('192.0.2.4', wp_json_encode($visits));
        $this->assertStringNotContainsString('private.example', wp_json_encode($visits));
        $this->assertSame([], $this->execute('list-affiliate-visits', ['affiliate_id' => $affiliate, 'per_page' => 1, 'page' => 2])['visits']);
        $statistics = $this->execute('get-affiliate-product-statistics', ['affiliate_id' => $affiliate, 'product_id' => $product]);
        $this->assertNotWPError($statistics);
        $this->assertSame(1, $statistics['statistics']['visits']);
        $deny = static function ($caps, $cap, $user, $args) use ($product) { return 'read_post' === $cap && (int) ($args[0] ?? 0) === $product ? ['do_not_allow'] : $caps; };
        add_filter('map_meta_cap', $deny, 10, 4);
        try {
            $this->assertWPError($this->execute('list-affiliate-visits', ['affiliate_id' => $affiliate]));
            $this->assertWPError($this->execute('get-affiliate-product-statistics', ['affiliate_id' => $affiliate, 'product_id' => $product]));
        } finally { remove_filter('map_meta_cap', $deny, 10); }
    }
}
