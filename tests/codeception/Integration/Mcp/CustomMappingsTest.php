<?php

declare(strict_types=1);
namespace Tests\Integration\Mcp;
use Tests\Support\Integration\McpTestCase;
require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class CustomMappingsTest extends McpTestCase
{
    public function test_declared_selectors_round_trip_and_never_read_arbitrary_metadata(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        ppcart_update_post_meta($id, 'custom_fields', [
            ['field_id' => 'company_size', 'field_label' => 'Company size', 'field_type' => 'text'],
            ['field_id' => 'password', 'field_label' => 'Password', 'field_type' => 'password'],
            ['field_id' => 'api_token', 'field_label' => 'Token', 'field_type' => 'text'],
        ]);
        $read = $this->execute('get-product-integrations', ['id' => $id]);
        $this->assertNotWPError($read);
        $this->assertContains('custom.company_size', $read['mapping_sources']);
        $this->assertNotContains('custom.password', $read['mapping_sources']);
        $this->assertNotContains('custom.api_token', $read['mapping_sources']);
        $rows = [['destination' => 'company', 'source' => 'custom.company_size']];
        $native = \PPCart_Mcp_Integration_Mapping::encode($rows, $id);
        $this->assertSame('company:custom.company_size', $native);
        $this->assertSame($rows, \PPCart_Mcp_Integration_Mapping::decode($native, $id));
        $this->assertWPError(\PPCart_Mcp_Integration_Mapping::encode([['destination' => 'company', 'source' => 'custom.api_token']], $id));
        $this->assertWPError(\PPCart_Mcp_Integration_Mapping::encode([['destination' => 'company', 'source' => 'receipt_key']], $id));
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        ppcart_update_post_meta($order, 'product_id', $id);
        ppcart_update_post_meta($order, 'custom_fields', ['company_size' => ['value' => ['small', 'medium']]]);
        ppcart_update_post_meta($order, 'custom.company_size', 'private-meta-sentinel');
        $this->assertSame(['small', 'medium'], ppcart_integration_custom_value('custom.company_size', $order));
        ppcart_update_post_meta($id, 'custom_fields', [['field_id' => 'company_size', 'field_label' => 'Company size', 'field_type' => 'password']]);
        $this->assertNull(ppcart_integration_custom_value('custom.company_size', $order));
        ppcart_update_post_meta($id, 'custom_fields', []);
        $this->assertNull(ppcart_integration_custom_value('custom.company_size', $order));
    }
    public function test_kit_configuration_saves_native_lines_and_sends_only_declared_values(): void
    {
        $provider = new \PublishPress\Cart\Kit();
        add_filter('ppcart_integrations', [$provider, 'add_service']);
        add_filter('ppcart_integration_fields', [$provider, 'add_integration_fields'], 10, 2);
        wp_unregister_ability('publishpress-cart/set-product-integrations');
        do_action('wp_abilities_api_init');
        update_option('ppcart_convertkit_forms', ['12' => 'Customers']);
        update_option('ppcart_convertkit_tags', ['34' => 'Buyer']);
        $sent = [];
        $http = static function ($result, $args, $url) use (&$sent) {
            $sent[] = is_string($args['body']) ? json_decode($args['body'], true) : $args['body'];
            return ['headers' => [], 'body' => '{}', 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []];
        };
        add_filter('pre_http_request', $http, 10, 3);
        try {
            $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
            ppcart_update_post_meta($id, 'custom_fields', [['field_id' => 'expertise', 'field_label' => 'Expertise', 'field_type' => 'text']]);
            $read = $this->execute('get-product-integrations', ['id' => $id]);
            $this->assertNotWPError($read);
            $row = (object) ['services' => 'convertkit', 'service_trigger' => ['purchased'], 'service_action' => 'subscribed',
                'convertkit_forms' => '12', 'convertkit_field_map' => [(object) ['destination' => 'expertise', 'source' => 'custom.expertise']]];
            $preview = $this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'], 'rows' => [$row], 'dry_run' => true]);
            $this->assertNotWPError($preview);
            $this->assertSame([], $sent);
            $saved = $this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'], 'rows' => [$row]]);
            $this->assertNotWPError($saved);
            $native = ppcart_get_post_meta($id, 'integrations', true);
            $this->assertSame('expertise:custom.expertise', $native[0]['convertkit_field_map']);
            $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
            ppcart_update_post_meta($order, 'product_id', $id);
            ppcart_update_post_meta($order, 'custom_fields', ['expertise' => ['value' => 'Publishing']]);
            ppcart_update_post_meta($order, 'custom.expertise', 'private-meta-sentinel');
            $provider->add_remove_to_service($native[0], $id, ['id' => $order, 'email' => 'synthetic@example.org']);
            $this->assertSame('Publishing', $sent[0]['fields']['expertise']);
            $this->assertStringNotContainsString('private-meta-sentinel', wp_json_encode($sent));
            $before = $native;
            $row->convertkit_field_map[0]->source = 'custom.unknown';
            $this->assertWPError($this->execute('set-product-integrations', ['id' => $id, 'revision' => $saved['revision'], 'rows' => [$row]]));
            $this->assertSame($before, ppcart_get_post_meta($id, 'integrations', true));
        } finally {
            remove_filter('pre_http_request', $http);
            remove_filter('ppcart_integrations', [$provider, 'add_service']);
            remove_filter('ppcart_integration_fields', [$provider, 'add_integration_fields'], 10);
            remove_action('plugins_loaded', [$provider, 'init']);
            wp_unregister_ability('publishpress-cart/set-product-integrations');
            do_action('wp_abilities_api_init');
        }
    }

}
