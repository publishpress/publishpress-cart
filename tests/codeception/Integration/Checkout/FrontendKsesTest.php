<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class FrontendKsesTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-359
     */
    public function test_IT_359_kses_preserves_plan_radio_price_and_installments(): void
    {
        $html = '<input type="radio" name="ppcart_product_option" data-price="11" data-installments="-1" data-val="recurring" data-testid="ppcart-payment-plan-1-input" value="reg_paypal_monthly" onclick="alert(1)">'
            . '<input type="radio" name="pay-method" value="paypal" data-testid="ppcart-payment-method-paypal-input">';

        $sanitized = ppcart_kses_frontend_html($html);

        $this->assertStringContainsString('data-price="11"', $sanitized);
        $this->assertStringContainsString('data-installments="-1"', $sanitized);
        $this->assertStringContainsString('data-val="recurring"', $sanitized);
        $this->assertStringContainsString('data-testid="ppcart-payment-plan-1-input"', $sanitized);
        $this->assertStringContainsString('name="pay-method"', $sanitized);
        $this->assertStringContainsString('value="paypal"', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
    }

    /**
     * @test-id IT-367
     */
    public function test_IT_367_checkout_buffers_strip_script_from_hook_output(): void
    {
        if (! function_exists('ppcart_do_remaining_card_details_fields')) {
            require_once PPCART_BASE_DIR . 'public/templates/template-functions.php';
        }

        $inject = static function () {
            echo '<p class="ppcart-kses-keep">kept</p><script>alert(1)</script><input type="text" data-testid="ppcart-kses-field" value="ok">';
        };

        add_action('ppcart_card_details_fields', $inject, 99, 3);
        ob_start();
        ppcart_do_remaining_card_details_fields(0, false, false);
        $fields = (string) ob_get_clean();
        remove_action('ppcart_card_details_fields', $inject, 99);

        $this->assertStringContainsString('ppcart-kses-keep', $fields);
        $this->assertStringContainsString('data-testid="ppcart-kses-field"', $fields);
        $this->assertStringNotContainsString('<script', $fields);

        add_action('ppcart_order_summary_items', $inject, 99, 3);
        ob_start();
        ppcart_render_order_summary_items(0, false);
        $summary = (string) ob_get_clean();
        remove_action('ppcart_order_summary_items', $inject, 99);

        $this->assertStringContainsString('ppcart-kses-keep', $summary);
        $this->assertStringContainsString('data-testid="ppcart-kses-field"', $summary);
        $this->assertStringNotContainsString('<script', $summary);
    }
}
