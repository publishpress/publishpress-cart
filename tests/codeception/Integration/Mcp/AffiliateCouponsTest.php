<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class AffiliateCouponsTest extends McpTestCase
{
    public function test_coupon_affiliate_identifiers_match_native_controls_and_checkout(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Affiliate coupons require Pro.');
        }
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
        ppcart_update_post_meta($product, 'affiliate_enable_disable', 1);
        $code = 'sample-' . wp_generate_password(8, false);
        $affiliate = \PPCart_Pro_Affiliate::create_affiliate(['first_name' => 'Sample', 'last_name' => 'Affiliate',
            'email' => $code . '@example.org', 'paypal_email' => '', 'personalized_identifier' => $code, 'status' => 'pending', 'user_id' => 0]);
        $this->assertIsInt($affiliate);
        $read = $this->execute('get-coupons', ['id' => $product]);
        $this->assertNotWPError($read);
        $this->assertSame('array', $read['fields']->affiliates['schema']['type']);
        add_filter('ppcart_process_stripe_products', '__return_false');
        try {
            $saved = $this->execute('set-coupons', ['id' => $product, 'revision' => $read['revision'], 'rows' => [(object) [
                'code' => 'SAVE-MORE', 'type' => 'fixed', 'amount' => '5', 'is_affiliate_coupon' => true, 'affiliates' => [$code],
            ]]]);
        } finally {
            remove_filter('ppcart_process_stripe_products', '__return_false');
        }
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($product, 'coupons', true);
        $this->assertSame([$code], $native[0]['affiliates']);
        $this->assertSame(1, $native[0]['is_affiliate_coupon']);
        $input = 'SAVE-MORE-' . $code;
        $this->assertSame('SAVE-MORE', apply_filters('ppcart_get_coupon_code', $input, $product, $native, []));
        $validated = apply_filters('ppcart_validate_coupon', [], $input, $product, []);
        $this->assertArrayNotHasKey('error', $validated);
        $this->assertSame($affiliate, (int) $validated['affiliate_id']);
        $this->assertArrayHasKey('error', apply_filters('ppcart_validate_coupon', [], 'SAVE-MORE-unknown', $product, []));
    }
}
