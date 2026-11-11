<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class RowRoundTripTest extends McpTestCase
{
    public function test_native_notification_checkbox_returns_a_reusable_json_boolean(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $native = [['notification_name' => 'Sample', 'subject' => 'Sample', 'message' => 'Sample', 'send_to' => 'purchaser', 'enabled' => 0]];
        ppcart_update_post_meta($id, 'notifications', $native);
        $read = $this->execute('get-product-notifications', ['id' => $id]);
        $this->assertNotWPError($read);
        $this->assertFalse($read['rows'][0]['enabled']);
        $result = $this->execute('set-product-notifications', ['id' => $id, 'revision' => $read['revision'], 'rows' => $read['rows'], 'dry_run' => true]);
        $this->assertNotWPError($result);
        $this->assertSame($native, ppcart_get_post_meta($id, 'notifications', true));
    }

    public function test_native_custom_field_zero_and_checkbox_match_writable_schema(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Custom fields require Pro.');
        }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $native = [['field_id' => 'sample', 'field_label' => 'Sample', 'field_type' => 'text', 'default_value' => 0, 'field_required' => 1]];
        ppcart_update_post_meta($id, 'custom_fields', $native);
        $read = $this->execute('get-custom-fields', ['id' => $id]);
        $this->assertNotWPError($read);
        $this->assertSame('0', $read['rows'][0]['default_value']);
        $this->assertTrue($read['rows'][0]['field_required']);
        $this->assertNotWPError($this->execute('set-custom-fields', ['id' => $id, 'revision' => $read['revision'], 'rows' => $read['rows'], 'dry_run' => true]));
        $this->assertSame($native, ppcart_get_post_meta($id, 'custom_fields', true));
    }
    public function test_native_empty_integration_multiselect_and_checkbox_are_reusable(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $provider = static function ($services) { $services['mailchimp'] = 'Mailchimp'; return $services; };
        add_filter('ppcart_integrations', $provider);
        $native = [['services' => 'mailchimp', 'service_trigger' => ['purchased'], 'service_action' => 'subscribed',
            'int_plan' => null, 'require_optin' => 0]];
        ppcart_update_post_meta($id, 'integrations', $native);
        try {
            $read = $this->execute('get-product-integrations', ['id' => $id]);
            $this->assertNotWPError($read);
            $this->assertSame([], $read['rows'][0]['int_plan']);
            $this->assertFalse($read['rows'][0]['require_optin']);
            $this->assertNotWPError($this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'],
                'rows' => $read['rows'], 'dry_run' => true]));
            $this->assertSame($native, ppcart_get_post_meta($id, 'integrations', true));
        } finally {
            remove_filter('ppcart_integrations', $provider);
        }
    }

}
