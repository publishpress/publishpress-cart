<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin;
use PPCart_Admin_Screens;
use PPCart_Public_Asset_Controller;

class JsGlobalsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-312
     */
    public function test_IT_312_settings_enqueue_localizes_canonical_admin_js_objects(): void
    {
        $this->enqueueOnHook(PPCart_Admin_Screens::HOOK_SETTINGS);

        $ppcart = $this->scriptData('ppcart');
        $this->assertStringContainsString('var ppcart_reg_vars', $ppcart);
        $this->assertStringContainsString('"ajax_url"', $ppcart);
        $this->assertStringContainsString('var ppcart_translate_backend', $ppcart);
        $this->assertStringContainsString('var ppcart_admin_i18n', $ppcart);
        $this->assertStringNotContainsString('var sc_reg_vars', $ppcart);
        $this->assertStringNotContainsString('"sc_ajax_url"', $ppcart);
        $this->assertStringNotContainsString('var sc_translate_backend', $ppcart);
        $this->assertStringNotContainsString('var sc_admin_i18n', $ppcart);

        $settings = $this->scriptData('ppcart-settings');
        $this->assertStringContainsString('var ppcartSettingsI18n', $settings);
        $this->assertStringNotContainsString('var ncsCartSettingsI18n', $settings);

        $repeater = $this->scriptData('ppcart-repeater');
        $this->assertStringContainsString('var ppcartNotificationI18n', $repeater);
        $this->assertStringNotContainsString('var ncsCartNotificationI18n', $repeater);
    }

    /**
     * @test-id IT-312
     */
    public function test_IT_312_reports_enqueue_localizes_ppcart_reports(): void
    {
        $this->enqueueOnHook(PPCart_Admin_Screens::HOOK_REPORTS);

        $reports = $this->scriptData('ppcart-reports');
        $this->assertStringContainsString('var ppcartReports', $reports);
        $this->assertStringNotContainsString('var ncsCartReports', $reports);
    }

    /**
     * @test-id IT-313
     */
    public function test_IT_313_public_enqueue_localizes_canonical_js_objects(): void
    {
        $this->enqueuePublicScripts();

        $data = $this->scriptData('ppcart');
        $this->assertMatchesRegularExpression('/var ppcart\s*=/', $data);
        $this->assertStringContainsString('"ajax"', $data);
        $this->assertStringContainsString('var ppcart_translate_frontend', $data);
        $this->assertStringContainsString('var ppcart_currency', $data);
        $this->assertStringContainsString('var ppcart_user', $data);
        $this->assertStringNotContainsString('var studiocart', $data);
        $this->assertStringNotContainsString('var sc_translate_frontend', $data);
        $this->assertStringNotContainsString('var sc_currency', $data);
        $this->assertStringNotContainsString('var sc_user', $data);
    }

    /**
     * @test-id IT-313
     */
    public function test_IT_313_checkout_coupon_inline_uses_ppcart_coupon(): void
    {
        $this->enqueuePublicScripts();
        if (! function_exists('ppcart_do_checkout_form_scripts')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }
        \ppcart_do_checkout_form_scripts(1, 'SAVE10');

        $before = wp_scripts()->get_data('ppcart', 'before');
        $this->assertNotFalse($before, 'ppcart has no before inline script');
        $inline = is_array($before) ? implode("\n", $before) : (string) $before;

        $this->assertStringContainsString('window.ppcart_coupon', $inline);
        $this->assertStringContainsString('SAVE10', $inline);
        $this->assertStringNotContainsString('window.sc_coupon', $inline);
    }

    private function enqueueOnHook(string $hook_suffix): void
    {
        set_current_screen($hook_suffix);

        $admin = new PPCart_Admin(
            'ppcart',
            'PublishPress Cart',
            defined('PPCART_VERSION') ? PPCART_VERSION : '1.0.0'
        );
        $admin->enqueue_scripts($hook_suffix);
    }

    private function enqueuePublicScripts(): void
    {
        $assets = new PPCart_Public_Asset_Controller(
            'ppcart',
            defined('PPCART_VERSION') ? PPCART_VERSION : '1.0.0',
            'ppcart_'
        );
        $assets->enqueue_scripts();
    }

    private function scriptData(string $handle): string
    {
        $data = wp_scripts()->get_data($handle, 'data');
        $this->assertNotFalse($data, $handle . ' has no localized extra data');

        return is_array($data) ? implode("\n", $data) : (string) $data;
    }
}
