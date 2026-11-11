<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class NativeControlsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Requires the native Pro bootstrap.');
        }
    }

    public function test_affiliate_role_lists_preserve_exact_native_types(): void
    {
        $before = $this->execute('get-settings', []);
        $this->assertSame('array', $before['settings']->_ppcart_affiliate_user_roles['schema']['type']);
        $patch = ['revision' => $before['revision'], 'changes' => (object) ['_ppcart_affiliate_user_roles' => ['subscriber']], 'dry_run' => true];
        $preview = $this->execute('update-settings', $patch);
        $this->assertNotWPError($preview);
        $this->assertSame($before['revision'], $this->execute('get-settings', [])['revision']);
        $patch['dry_run'] = false;
        $saved = $this->execute('update-settings', $patch);
        $this->assertNotWPError($saved);
        $this->assertSame(['subscriber'], get_option('_ppcart_affiliate_user_roles'));
        foreach (['subscriber', (object) ['subscriber' => true], [false], ['missing-role']] as $invalid) {
            $this->assertWPError($this->execute('update-settings', ['revision' => $saved['revision'], 'changes' => (object) [
                '_ppcart_company_name' => 'Must not save', '_ppcart_affiliate_user_roles' => $invalid,
            ]]));
            $this->assertSame(['subscriber'], get_option('_ppcart_affiliate_user_roles'));
        }
        $cleared = $this->execute('update-settings', ['revision' => $saved['revision'], 'changes' => (object) ['_ppcart_affiliate_user_roles' => []]]);
        $this->assertNotWPError($cleared);
        $this->assertSame([], get_option('_ppcart_affiliate_user_roles'));
    }

    public function test_conditional_confirmations_native_round_trip_and_atomic_validation(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $before = $this->execute('get-confirmations', ['id' => $id]);
        $this->assertNotWPError($before);
        $row = (object) ['name' => 'Thanks', 'enabled' => true, 'condition_type' => 'and', 'conditions' => [],
            'confirmation_type' => 'redirect', 'confirmation_redirect' => 'https://example.org/thanks'];
        $patch = ['id' => $id, 'revision' => $before['revision'], 'rows' => [$row], 'dry_run' => true];
        $this->assertNotWPError($this->execute('set-confirmations', $patch));
        $this->assertSame('', ppcart_get_post_meta($id, 'confirmations', true));
        $patch['dry_run'] = false;
        $saved = $this->execute('set-confirmations', $patch);
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'confirmations', true);
        $this->assertSame(1, $native[0]['enabled']);
        $this->assertSame('https://example.org/thanks', $native[0]['confirmation_redirect']);
        $bad = clone $row;
        $bad->conditions = [(object) ['action' => 'field-value', 'cfield' => 'missing', 'cfield_compare' => 'is', 'cfield_value' => 'yes']];
        $this->assertWPError($this->execute('set-confirmations', ['id' => $id, 'revision' => $saved['revision'], 'rows' => [$row, $bad]]));
        $this->assertSame($native, ppcart_get_post_meta($id, 'confirmations', true));
    }

    public function test_affiliate_commission_types_and_disabled_extension_permissions(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $before = $this->execute('get-affiliate-commissions', ['id' => $id]);
        $this->assertNotWPError($before);
        $saved = $this->execute('set-affiliate-commissions', ['id' => $id, 'revision' => $before['revision'], 'rows' => [(object) [
            'affiliate_commission' => 'Base', 'affiliate_price' => [], 'affiliate_type' => 'percentage',
            'affiliate_percentage' => 20, 'affiliate_apply_to' => 'product_only', 'renewal_limit' => '2',
        ]]]);
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'affiliate_commission_fields', true);
        $this->assertSame(20, $native[0]['affiliate_percentage']);
        $this->assertSame('2', $native[0]['renewal_limit']);
        $extensions = $this->execute('get-extensions', []);
        $preview = $this->execute('set-extension', ['extension' => 'affiliate', 'enabled' => false, 'revision' => $extensions['revision'], 'dry_run' => true]);
        $this->assertNotWPError($preview);
        $this->assertTrue(\ppcart_pro_mcp_extension_enabled('affiliate'));
        $disabled = $this->execute('set-extension', ['extension' => 'affiliate', 'enabled' => false, 'revision' => $extensions['revision']]);
        $this->assertNotWPError($disabled);
        $this->assertWPError($this->execute('get-affiliate-commissions', ['id' => $id]));
        $settings = $this->execute('get-settings', []);
        $this->assertFalse(isset($settings['settings']->_ppcart_affiliate_user_roles));
        $this->assertWPError($this->execute('set-extension', ['extension' => 'affiliate', 'enabled' => true, 'revision' => $extensions['revision']]));
        $this->assertNotWPError($this->execute('set-extension', ['extension' => 'affiliate', 'enabled' => true, 'revision' => $disabled['revision']]));
    }
}
