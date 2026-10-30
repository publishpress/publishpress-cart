<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class EditionTest extends McpTestCase
{
    public function test_active_edition_controls_native_setting_and_plan_exposure(): void
    {
        $pro = 'pro' === getenv('CART_MCP_EDITION');
        $this->assertSame($pro, ppcart_is_pro());
        $settings = $this->execute('get-settings', []);
        $this->assertNotWPError($settings);
        $fields = (array) $settings['settings'];
        $this->assertSame($pro, isset($fields['_ppcart_email_trial_ending_days']));
        $this->assertArrayHasKey('_ppcart_tax_enable', $fields);
        $this->assertArrayHasKey('_ppcart_enable_invoice_number', $fields);
        $this->assertArrayNotHasKey('_ppcart_api_key', $fields);
        $this->assertArrayNotHasKey('_ppcart_paypal_secret', $fields);
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $plans = $this->execute('get-payment-plans', ['id' => $id]);
        $this->assertNotWPError($plans);
        $this->assertSame($pro, isset($plans['fields']->trial_days));
        $products = $this->execute('get-product-settings', ['id' => $id]);
        $this->assertNotWPError($products);
        $this->assertSame($pro, isset($products['fields']->_ppcart_hide_labels));
        $this->assertSame($pro, isset($products['fields']->_ppcart_qty_field));
    }
}
