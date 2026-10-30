<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class TransferTest extends McpTestCase
{
    public function test_secure_upload_is_private_and_assignable_with_native_permissions(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $bytes = 'Synthetic download content';
        $request = ['id' => $id, 'filename' => 'mcp-private.txt', 'content_base64' => base64_encode($bytes), 'sha256' => hash('sha256', $bytes)];
        $invalid = $request;
        $invalid['filename'] = '../unsafe.txt';
        $this->assertWPError($this->execute('upload-product-file', $invalid));
        $invalid = $request;
        $invalid['sha256'] = str_repeat('0', 64);
        $this->assertWPError($this->execute('upload-product-file', $invalid));
        wp_get_current_user()->add_cap('upload_files', false);
        $this->assertWPError($this->execute('upload-product-file', $request));
        wp_get_current_user()->add_cap('upload_files', true);
        $saved = $this->execute('upload-product-file', $request);
        $this->assertNotWPError($saved);
        $attachment = $saved['attachment_id'];
        try {
            $this->assertSame($bytes, file_get_contents(get_attached_file($attachment)));
            $this->assertSame($id, wp_get_post_parent_id($attachment));
            $this->assertNotFalse(\PPCart_Files::resolve_local_download_path(get_attached_file($attachment)));
            $this->assertStringNotContainsString('url', wp_json_encode($saved));
            $before = $this->execute('get-product-files', ['id' => $id]);
            $assigned = $this->execute('set-product-files', ['id' => $id, 'revision' => $before['revision'],
                'rows' => [['file_id' => 'manual', 'file_name' => 'Manual', 'attachment_id' => $attachment]]]);
            $this->assertNotWPError($assigned);
        } finally {
            wp_delete_attachment($attachment, true);
        }
    }

    public function test_mapping_uses_safe_structured_pairs_and_preserves_hidden_authentication(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $provider = static function ($services) { $services['activecampaign'] = 'ActiveCampaign'; return $services; };
        add_filter('ppcart_integrations', $provider);
        ppcart_update_post_meta($id, 'integrations', [['services' => 'activecampaign', 'service_trigger' => ['purchased'],
            'hook_headers' => 'Bearer synthetic-private', 'activecampaign_field_map' => 'private:"synthetic-secret"']]);
        try {
            $read = $this->execute('get-product-integrations', ['id' => $id]);
            $this->assertNotWPError($read);
            $this->assertStringNotContainsString('synthetic-', wp_json_encode($read));
            $map = [['destination' => 'EMAIL', 'source' => 'customer_email']];
            $request = ['id' => $id, 'revision' => $read['revision'], 'rows' => [['row_id' => 0, 'activecampaign_field_map' => $map]], 'dry_run' => true];
            $preview = $this->execute('set-product-integrations', $request);
            $this->assertNotWPError($preview);
            $this->assertSame('private:"synthetic-secret"', ppcart_get_post_meta($id, 'integrations', true)[0]['activecampaign_field_map']);
            $request['dry_run'] = false;
            $saved = $this->execute('set-product-integrations', $request);
            $this->assertNotWPError($saved);
            $native = ppcart_get_post_meta($id, 'integrations', true);
            $this->assertSame('EMAIL:customer_email', $native[0]['activecampaign_field_map']);
            $this->assertSame('Bearer synthetic-private', $native[0]['hook_headers']);
            $this->assertSame($map, $saved['rows'][0]['activecampaign_field_map']);
            $request['revision'] = $saved['revision'];
            $request['rows'][0]['activecampaign_field_map'][] = ['destination' => 'SECRET', 'source' => 'receipt_key'];
            $this->assertWPError($this->execute('set-product-integrations', $request));
            $this->assertSame($native, ppcart_get_post_meta($id, 'integrations', true));
        } finally {
            remove_filter('ppcart_integrations', $provider);
        }
    }

    public function test_pro_coupon_csv_append_round_trip_and_atomic_preflight(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Pro CSV imports.'); }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $before = $this->execute('export-pro-csv', ['feature' => 'coupons', 'id' => $id]);
        $this->assertNotWPError($before);
        $request = ['feature' => 'coupons', 'id' => $id, 'revision' => $before['revision'],
            'csv' => "code,type,amount,limit,plan,single_use\nNEW10,percent,10,,,0\n", 'dry_run' => true];
        $this->assertNotWPError($this->execute('import-pro-csv', $request));
        $this->assertSame('', ppcart_get_post_meta($id, 'coupons', true));
        $request['dry_run'] = false;
        add_filter('ppcart_process_stripe_products', '__return_false');
        try {
            $saved = $this->execute('import-pro-csv', $request);
            $this->assertNotWPError($saved);
            $this->assertSame('NEW10', ppcart_get_post_meta($id, 'coupons', true)[0]['code']);
            $export = $this->execute('export-pro-csv', ['feature' => 'coupons', 'id' => $id]);
            $this->assertNotWPError($export);
            $request['revision'] = $export['revision'];
            $request['csv'] = "code,type,amount\nVALID,fixed,5\nINVALID,percent,101\n";
            $old = ppcart_get_post_meta($id, 'coupons', true);
            $this->assertWPError($this->execute('import-pro-csv', $request));
            $this->assertSame($old, ppcart_get_post_meta($id, 'coupons', true));
            $request['csv'] = "code,type,amount\nSECOND,fixed,5\n";
            $this->assertNotWPError($this->execute('import-pro-csv', $request));
            $this->assertCount(2, ppcart_get_post_meta($id, 'coupons', true));
        } finally {
            remove_filter('ppcart_process_stripe_products', '__return_false');
        }
    }

    public function test_pro_tax_csv_validates_all_rows_before_native_writes(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Pro tax imports.'); }
        $before = $this->execute('export-pro-csv', ['feature' => 'tax-rates']);
        $this->assertNotWPError($before);
        $request = ['feature' => 'tax-rates', 'revision' => $before['revision'],
            'csv' => "Country,State,Postcode,City,Rate,Name,Priority\nUS,CA,,,5.25,Example,1\n", 'dry_run' => true];
        $this->assertNotWPError($this->execute('import-pro-csv', $request));
        $this->assertSame($before, $this->execute('export-pro-csv', ['feature' => 'tax-rates']));
        $bad = $request;
        $bad['csv'] .= "US,CA,,,101,Bad,1\n";
        $bad['dry_run'] = false;
        $this->assertWPError($this->execute('import-pro-csv', $bad));
        $this->assertSame($before, $this->execute('export-pro-csv', ['feature' => 'tax-rates']));
        $request['dry_run'] = false;
        $saved = $this->execute('import-pro-csv', $request);
        $this->assertNotWPError($saved);
        $this->assertCount(1, $saved['imported_ids']);
        $export = $this->execute('export-pro-csv', ['feature' => 'tax-rates']);
        $this->assertStringContainsString('Example', $export['csv']);
    }
    public function test_pro_tax_import_reports_partial_gateway_failure_without_retrying(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Pro tax import failures.'); }
        global $PPCart_Pro_Admin_Tax;
        $previous = $PPCart_Pro_Admin_Tax;
        $controller = new \PPCart_Pro_Admin_Tax();
        $gateway = new class {
            public $taxRates;
            public function __construct()
            {
                $this->taxRates = new class {
                    public $calls = 0;
                    public function create($args)
                    {
                        $this->calls++;
                        if (2 === $this->calls) {
                            throw new \RuntimeException('Synthetic private gateway error');
                        }
                        return (object) ['id' => 'synthetic-tax-' . $this->calls];
                    }
                };
            }
        };
        $property = new \ReflectionProperty($controller, 'stripe');
        $property->setAccessible(true);
        $property->setValue($controller, $gateway);
        $PPCart_Pro_Admin_Tax = $controller;
        try {
            $before = $this->execute('export-pro-csv', ['feature' => 'tax-rates']);
            $result = $this->execute('import-pro-csv', ['feature' => 'tax-rates', 'revision' => $before['revision'],
                'csv' => "Country,State,Postcode,City,Rate,Name,Priority\nUS,CA,,,5,Completed,1\nUS,NY,,,6,Unconfirmed,1\n"]);
            $this->assertWPError($result);
            $this->assertSame('ppcart_mcp_import_partial', $result->get_error_code());
            $this->assertCount(1, $result->get_error_data()['completed_ids']);
            $this->assertSame(2, $result->get_error_data()['failed_row']);
            $this->assertSame(2, $gateway->taxRates->calls);
            $this->assertStringNotContainsString('Synthetic private', $result->get_error_message());
            $after = $this->execute('list-tax-rates', []);
            $this->assertContains('Completed', array_column($after['rates'], 'tax_rate_title'));
            $this->assertContains('Unconfirmed', array_column($after['rates'], 'tax_rate_title'));
        } finally {
            $PPCart_Pro_Admin_Tax = $previous;
        }
    }

}
