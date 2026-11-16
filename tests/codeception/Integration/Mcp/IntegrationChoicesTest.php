<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class IntegrationChoicesTest extends McpTestCase
{
    public function test_remote_choice_discovery_is_separate_from_network_free_previews(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Uses the native Pro Heartbeat field provider.');
        }
        $key = static function () { return 'synthetic-provider-key'; };
        add_filter('pre_option__ppcart_heartbeat_api_key', $key);
        $provider = new \PPCart_Pro_HeartBeat();
        remove_filter('pre_option__ppcart_heartbeat_api_key', $key);
        add_filter('ppcart_integrations', [$provider, 'add_heartbeat_service']);
        add_filter('ppcart_integration_fields', [$provider, 'add_integration_fields'], 10, 2);
        wp_unregister_ability('publishpress-cart/set-product-integrations');
        do_action('wp_abilities_api_init');
        $calls = 0;
        $http = static function ($result, $args, $url) use (&$calls) {
            if (0 !== strpos($url, 'https://api.heartbeat.chat/')) {
                return new \WP_Error('unexpected_http', 'Unexpected fixture request.');
            }
            ++$calls;
            $path = wp_parse_url($url, PHP_URL_PATH);
            $rows = '/v0/roles' === $path ? [['id' => 123, 'name' => 'Member']]
                : ('/v0/groups' === $path ? [['id' => 456, 'name' => 'Customers']] : [['id' => 789, 'code' => 'private-invitation']]);
            return ['headers' => [], 'body' => wp_json_encode($rows), 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []];
        };
        add_filter('pre_http_request', $http, 10, 3);
        $writes = 0;
        $observe = static function () use (&$writes) { ++$writes; };
        try {
            $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
            $read = $this->execute('get-product-integrations', ['id' => $id]);
            $this->assertNotWPError($read);
            $this->assertGreaterThan(0, $calls);
            $this->assertStringNotContainsString('private-invitation', wp_json_encode($read));
            $this->assertArrayHasKey(123, (array) $read['fields']->heartbeat_user_role['choices']);
            $count = $calls;
            add_action('updated_option', $observe);
            $preview = $this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'], 'dry_run' => true,
                'rows' => [(object) ['services' => 'heartbeat', 'service_trigger' => ['purchased'], 'heartbeat_action' => 'add_user',
                    'heartbeat_user_role' => '123', 'heartbeat_user_group' => '456']]]);
            $this->assertNotWPError($preview);
            $this->assertSame($count, $calls);
            $this->assertSame(0, $writes);
            remove_action('updated_option', $observe);
            update_option('_ppcart_company_name', 'New native configuration');
            $expired = $this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'], 'rows' => [], 'dry_run' => true]);
            $this->assertWPError($expired);
            $this->assertSame('ppcart_mcp_choices_expired', $expired->get_error_code());
            $this->assertSame($count, $calls);
        } finally {
            remove_action('updated_option', $observe);
            remove_filter('pre_http_request', $http, 10);
            remove_filter('ppcart_integrations', [$provider, 'add_heartbeat_service']);
            remove_filter('ppcart_integration_fields', [$provider, 'add_integration_fields'], 10);
            wp_unregister_ability('publishpress-cart/set-product-integrations');
            do_action('wp_abilities_api_init');
            remove_action('plugins_loaded', [$provider, 'init']);
        }
    }

    public function test_store_discovery_does_not_fetch_square_credential_setup_locations(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Square is a native bundled Pro extension.');
        }
        global $wp_filter;
        $provider = null;
        foreach ($wp_filter['ppcart_payment_field_option_list']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && '_ppcart_pro_squarepay_option' === $callback['function'][1]) {
                    $provider = $callback['function'][0];
                }
            }
        }
        $this->assertNotNull($provider);
        $api = new \ReflectionProperty($provider, 'squarepayApi');
        $api->setAccessible(true);
        $mode = new \ReflectionProperty($provider, 'squarepayMode');
        $mode->setAccessible(true);
        $previous_api = $api->getValue($provider);
        $previous_mode = $mode->getValue($provider);
        $client = new class {
            public $calls = 0;
            public function getLocationsApi() { return $this; }
            public function listLocations() { ++$this->calls; return $this; }
            public function isSuccess() { return false; }
            public function getErrors() { return []; }
        };
        $api->setValue($provider, $client);
        $mode->setValue($provider, 'sandbox');
        try {
            $read = $this->execute('get-settings', []);
            $this->assertNotWPError($read);
            $this->assertSame(0, $client->calls);
            $this->assertNotWPError($this->execute('update-settings', ['revision' => $read['revision'], 'changes' => (object) ['_ppcart_squarepay_enable' => false], 'dry_run' => true]));
            $this->assertSame(0, $client->calls);
            $provider->_ppcart_pro_squarepay_option([]);
            $this->assertSame(1, $client->calls);
        } finally {
            $api->setValue($provider, $previous_api);
            $mode->setValue($provider, $previous_mode);
        }
    }
}
