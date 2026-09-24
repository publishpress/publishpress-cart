<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin;

class TaxCsvImportSlugTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-358
     */
    public function test_IT_358_register_importers_fires_canonical_hook_and_registers_slug(): void
    {
        if (! defined('WP_LOAD_IMPORTERS')) {
            define('WP_LOAD_IMPORTERS', true);
        }

        require_once ABSPATH . 'wp-admin/includes/import.php';

        $hook_fired = false;
        add_action(
            'ppcart_register_importers',
            static function () use (&$hook_fired): void {
                $hook_fired = true;
                register_importer(
                    'ppcart_tax_rate_csv',
                    'PublishPress Cart tax rates (CSV)',
                    'Import tax rates via CSV.',
                    '__return_null'
                );
            }
        );

        $admin = new PPCart_Admin(
            'ppcart',
            'PublishPress Cart',
            defined('PPCART_VERSION') ? PPCART_VERSION : '1.0.0'
        );
        $admin->register_importers();

        $this->assertTrue($hook_fired, 'ppcart_register_importers must fire when WP_LOAD_IMPORTERS is defined.');

        global $wp_importers;
        $this->assertIsArray($wp_importers);
        $this->assertArrayHasKey('ppcart_tax_rate_csv', $wp_importers);
        $this->assertArrayNotHasKey('ncs-cart_tax_rate_csv', $wp_importers);
    }
}
