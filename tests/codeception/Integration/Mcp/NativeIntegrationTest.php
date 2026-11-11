<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class NativeIntegrationTest extends McpTestCase
{
    public function test_affiliate_configuration_uses_current_product_fields_and_native_assignment_storage(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Native affiliate integration requires Pro.');
        }
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
        $target = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
        ppcart_update_post_meta($target, 'affiliate_enable_disable', 1);
        ppcart_update_post_meta($product, 'custom_fields', [
            ['field_id' => 'expertise', 'field_label' => 'Expertise', 'field_type' => 'text'],
            ['field_id' => 'private_password', 'field_label' => 'Password', 'field_type' => 'password'],
        ]);
        $read = $this->execute('get-product-integrations', ['id' => $product]);
        $this->assertNotWPError($read);
        $this->assertArrayHasKey('expertise', (array) $read['fields']->bio_field_id['choices']);
        $this->assertArrayNotHasKey('private_password', (array) $read['fields']->bio_field_id['choices']);
        $row = (object) ['services' => 'affiliate', 'service_trigger' => ['purchased'], 'affiliate_status' => 'pending',
            'affiliate_product' => [(string) $target], 'bio_field_id' => 'expertise', 'meta_field_ids' => 'expertise'];
        $saved = $this->execute('set-product-integrations', ['id' => $product, 'revision' => $read['revision'], 'rows' => [$row]]);
        $this->assertNotWPError($saved);
        $bad = clone $row;
        $bad->bio_field_id = 'private_password';
        $this->assertWPError($this->execute('set-product-integrations', ['id' => $product, 'revision' => $saved['revision'], 'rows' => [$bad]]));
        $email = 'integration-' . wp_generate_password(8, false) . '@example.org';
        $user = self::factory()->user->create(['role' => 'subscriber', 'user_email' => $email]);
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        $rules = ppcart_get_post_meta($product, 'integrations', true);
        do_action('ppcart_affiliate_integrations', $rules[0], $product, ['id' => $order, 'first_name' => 'Native', 'last_name' => 'Affiliate',
            'email' => $email, 'custom_fields' => ['expertise' => 'Testing']]);
        $affiliate = \PPCart_Pro_Affiliate::find_by_user_id($user);
        $this->assertNotNull($affiliate);
        $this->assertSame('Testing', $affiliate->get_meta('bio'));
        $this->assertSame([$target], array_map('intval', $affiliate->get_meta('affiliate_product', false)));
        $this->assertSame([], get_post_meta((int) $affiliate->id, 'affiliate_product', false));
    }
}
