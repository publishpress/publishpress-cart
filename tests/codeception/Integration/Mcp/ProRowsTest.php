<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ProRowsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Pro product-row tools are tested with the actual Pro bootstrap.');
        }
    }

    public function test_coupon_preview_save_and_invalid_rows_preserve_existing_data(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $before = $this->execute('get-coupons', ['id' => $id]);
        $patch = ['id' => $id, 'revision' => $before['revision'], 'rows' => [(object) [
            'code' => 'TEST10', 'type' => 'percent', 'amount' => '10', 'limit' => null, 'plan' => [],
        ]], 'dry_run' => true];
        $preview = $this->execute('set-coupons', $patch);
        $this->assertNotWPError($preview);
        $this->assertSame('', ppcart_get_post_meta($id, 'coupons', true));
        add_filter('ppcart_process_stripe_products', '__return_false');
        try {
            $patch['dry_run'] = false;
            $saved = $this->execute('set-coupons', $patch);
        } finally {
            remove_filter('ppcart_process_stripe_products', '__return_false');
        }
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'coupons', true);
        $this->assertSame('TEST10', $native[0]['code']);
        $this->assertSame('10', $native[0]['amount']);
        $this->assertNull($native[0]['limit']);
        $bad = ['id' => $id, 'revision' => $saved['revision'], 'rows' => [
            (object) ['code' => 'VALID', 'type' => 'fixed', 'amount' => '5'],
            (object) ['code' => 'INVALID', 'type' => 'percent', 'amount' => '101'],
        ]];
        $this->assertWPError($this->execute('set-coupons', $bad));
        $this->assertSame($native, ppcart_get_post_meta($id, 'coupons', true));
    }

    public function test_custom_field_round_trip_and_password_default_redaction(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $before = $this->execute('get-custom-fields', ['id' => $id]);
        $saved = $this->execute('set-custom-fields', ['id' => $id, 'revision' => $before['revision'], 'rows' => [(object) [
            'field_id' => 'expertise', 'field_label' => 'Expertise', 'field_type' => 'text',
            'field_required' => false, 'default_value' => null,
        ]]]);
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'custom_fields', true);
        $this->assertSame('expertise', $native[0]['field_id']);
        $this->assertSame(0, $native[0]['field_required']);
        $this->assertNull($native[0]['default_value']);
        ppcart_update_post_meta($id, 'custom_fields', [[
            'field_id' => 'password', 'field_type' => 'password', 'field_label' => 'Password', 'default_value' => 'synthetic-secret',
        ]]);
        $read = $this->execute('get-custom-fields', ['id' => $id]);
        $this->assertStringNotContainsString('synthetic-secret', wp_json_encode($read));
        $this->assertWPError($this->execute('set-custom-fields', ['id' => $id, 'revision' => $read['revision'], 'rows' => [(object) [
            'field_id' => 'password', 'field_type' => 'text',
        ]]]));
    }

    public function test_pro_gateway_failure_throws_instead_of_terminating_api_requests(): void
    {
        $gateway = new class {
            public $coupons;
            public function __construct()
            {
                $this->coupons = new class {
                    public function create($args)
                    {
                        throw new \RuntimeException('Synthetic gateway failure');
                    }
                };
            }
        };
        try {
            \PPCart_Pro_Stripe_Coupons::with_exceptions(static function () use ($gateway) {
                \PPCart_Pro_Stripe_Coupons::create_coupon__premium_only(false, 'synthetic', '1', 'fixed', 'USD', 0, 'forever', null, $gateway);
            });
            $this->fail('Expected a recoverable gateway exception.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic gateway failure', $error->getMessage());
        }
    }
    public function test_additional_bumps_preserve_native_positional_rows(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $target = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
        $before = $this->execute('get-order-bumps', ['id' => $id]);
        $this->assertNotWPError($before);
        $rows = [(object) ['ob_product' => (string) $target, 'ob_type' => '', 'ob_price' => '0', 'ob_headline' => 'First'],
            (object) ['ob_product' => (string) $target, 'ob_type' => '', 'ob_price' => '0', 'ob_headline' => 'Second']];
        $saved = $this->execute('set-order-bumps', ['id' => $id, 'revision' => $before['revision'], 'rows' => $rows]);
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'order_bump_options', true);
        $this->assertCount(2, $native);
        $this->assertSame('First', $native[0]['ob_headline']);
        $this->assertSame('Second', $native[1]['ob_headline']);
    }

}
