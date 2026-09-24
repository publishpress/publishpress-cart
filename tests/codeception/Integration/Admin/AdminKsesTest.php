<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;

class AdminKsesTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-368
     */
    public function test_IT_368_admin_kses_keeps_settings_attrs_and_svg_stroke(): void
    {
        $html = '<aside class="ppcart-settings__payment-panel" role="dialog" aria-modal="true" aria-labelledby="ppcart-payment-stripe-title" data-pp-payment-detail="stripe" hidden></aside>'
            . '<input type="text" form="ppcart-stripe-webhook-form-test" data-pp-payment-toggle="stripe" data-pp-secret-stored="1" data-testid="ppcart-admin-payment-stripe-toggle" onclick="alert(1)">'
            . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="ppcart-nav-icon" cx="12" cy="12" r="3"/></svg>'
            . '<script>alert(1)</script>';

        $sanitized = wp_kses($html, ppcart_admin_allowed_html());

        $this->assertStringContainsString('role="dialog"', $sanitized);
        $this->assertStringContainsString('aria-modal="true"', $sanitized);
        $this->assertStringContainsString('aria-labelledby="ppcart-payment-stripe-title"', $sanitized);
        $this->assertStringContainsString('data-pp-payment-detail="stripe"', $sanitized);
        $this->assertStringContainsString('form="ppcart-stripe-webhook-form-test"', $sanitized);
        $this->assertStringContainsString('data-pp-payment-toggle="stripe"', $sanitized);
        $this->assertStringContainsString('data-pp-secret-stored="1"', $sanitized);
        $this->assertStringContainsString('stroke="currentColor"', $sanitized);
        $this->assertStringContainsString('class="ppcart-nav-icon"', $sanitized);
        $this->assertStringContainsString('<circle', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('<script', $sanitized);
    }
}
