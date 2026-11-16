<?php

declare(strict_types=1);
namespace Tests\Integration\Mcp;
use Tests\Support\Integration\McpTestCase;
require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class NativeProviderPayloadTest extends McpTestCase
{
    public function test_encharge_uses_the_order_instead_of_reading_a_product_as_an_order(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Native Encharge integration requires Pro.');
        }
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_title' => 'Native product']);
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        foreach (['product_id' => $product, 'email' => 'synthetic@example.org', 'first_name' => 'Synthetic', 'last_name' => 'Customer',
            'status' => 'paid', 'amount' => '12', 'currency' => 'USD'] as $key => $value) {
            ppcart_update_post_meta($order, $key, $value);
        }
        $provider = new \PPCart_Pro_Encharge();
        $native = (new \PPCart_Order($order))->get_data();
        $data = $provider->get_event_data(['encharge_event_name' => 'Purchase', 'encharge_properties' => 'campaign:Newsletter'], $native);
        $this->assertSame($order, (int) $data['properties']['id']);
        $this->assertSame($product, (int) $data['properties']['product_id']);
        $this->assertSame('synthetic@example.org', $data['user']['email']);
        $this->assertSame('Newsletter', $data['properties']['campaign']);
    }
}
