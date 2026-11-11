<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ProTaxTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Custom tax rates require the actual Pro bootstrap.');
        }
    }

    public function test_native_tax_preview_crud_types_revisions_and_permissions(): void
    {
        $before = $this->execute('list-tax-rates', []);
        $this->assertNotWPError($before);
        $changes = (object) ['tax_rate_country' => 'us', 'tax_rate_state' => '*', 'tax_rate_city' => ' Boston ',
            'tax_rate_postcode' => 'AB 12', 'tax_rate_title' => 'Regional Tax', 'tax_rate' => '7.5', 'tax_rate_priority' => 2];
        $input = ['changes' => $changes, 'revision' => $before['revision'], 'dry_run' => true];
        $preview = $this->execute('save-tax-rate', $input);
        $this->assertNotWPError($preview);
        $this->assertSame('7.50', $preview['changes']->tax_rate);
        $this->assertSame('', $preview['changes']->tax_rate_state);
        $this->assertEquals($before, $this->execute('list-tax-rates', []));
        $input['dry_run'] = false;
        $saved = $this->execute('save-tax-rate', $input);
        $this->assertNotWPError($saved);
        $id = $saved['id'];
        $native = \PPCart_Tax::get_tax_rate_by_id($id);
        $this->assertSame('US', $native['tax_rate_country']);
        $this->assertSame('BOSTON', $native['tax_rate_city']);
        $this->assertSame('ab12', $native['tax_rate_postcode']);
        $this->assertSame('7.50', $native['tax_rate']);
        $this->assertStringNotContainsString('stripe_tax_id', wp_json_encode($saved));
        foreach ([(object) ['tax_rate' => 9], (object) ['tax_rate_priority' => '1'], (object) ['tax_rate' => '101'],
            (object) ['tax_rate_title' => 'Must not save', 'tax_rate_meta' => (object) ['stripe_tax_id' => 'forged']]] as $bad) {
            $this->assertWPError($this->execute('save-tax-rate', ['id' => $id, 'revision' => $saved['revision'], 'changes' => $bad]));
            $this->assertSame($native, \PPCart_Tax::get_tax_rate_by_id($id));
        }
        $this->assertWPError($this->execute('save-tax-rate', ['id' => $id, 'revision' => $before['revision'], 'changes' => (object) ['tax_rate' => '8']]));
        $updated = $this->execute('save-tax-rate', ['id' => $id, 'revision' => $saved['revision'], 'changes' => (object) ['tax_rate' => '8']]);
        $this->assertNotWPError($updated);
        $this->assertSame('US', \PPCart_Tax::get_tax_rate_by_id($id)['tax_rate_country']);
        $deleted = $this->execute('delete-tax-rate', ['id' => $id, 'revision' => $updated['revision']]);
        $this->assertNotWPError($deleted);
        $this->assertNotContains($id, array_column($deleted['rates'], 'id'));
        $deny = static function ($caps) { $caps['manage_options'] = false; return $caps; };
        add_filter('user_has_cap', $deny);
        try {
            $this->assertWPError($this->execute('list-tax-rates', []));
        } finally {
            remove_filter('user_has_cap', $deny);
        }
    }

    public function test_pro_repeater_zero_uses_native_integer_storage(): void
    {
        $this->assertSame(0, \PPCart_Pro_Mcp_Row_Fields::sanitize('0', ['type' => 'price']));
        $this->assertNull(\PPCart_Pro_Mcp_Row_Fields::sanitize('', ['type' => 'text']));
        $this->assertSame(0, \PPCart_Pro_Mcp_Row_Fields::sanitize(false, ['type' => 'checkbox']));
    }
    public function test_gateway_failure_is_recoverable_and_reports_unconfirmed_local_changes(): void
    {
        global $PPCart_Pro_Admin_Tax;
        $previous = $PPCart_Pro_Admin_Tax;
        $controller = new \PPCart_Pro_Admin_Tax();
        $gateway = new class {
            public $taxRates;
            public function __construct()
            {
                $this->taxRates = new class {
                    public function create($args)
                    {
                        throw new \RuntimeException('Synthetic tax gateway failure');
                    }
                };
            }
        };
        $property = new \ReflectionProperty($controller, 'stripe');
        $property->setAccessible(true);
        $property->setValue($controller, $gateway);
        $PPCart_Pro_Admin_Tax = $controller;
        try {
            $before = $this->execute('list-tax-rates', []);
            $result = $this->execute('save-tax-rate', ['revision' => $before['revision'], 'changes' => (object) [
                'tax_rate_title' => 'Unconfirmed test rate', 'tax_rate' => '5',
            ]]);
            $this->assertWPError($result);
            $this->assertSame('ppcart_mcp_tax_unconfirmed', $result->get_error_code());
            $this->assertStringNotContainsString('Synthetic tax gateway failure', $result->get_error_message());
            $after = $this->execute('list-tax-rates', []);
            $this->assertContains('Unconfirmed test rate', array_column($after['rates'], 'tax_rate_title'));
            $this->assertFalse(get_option('_ppcart_mcp_tax_lock'));
        } finally {
            $PPCart_Pro_Admin_Tax = $previous;
        }
    }

}
