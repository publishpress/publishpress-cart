<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class CheckoutRequiredFieldsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('ppcart_do_field')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }
    }

    private function renderField(array $args): string
    {
        ob_start();
        ppcart_do_field($args);

        return (string) ob_get_clean();
    }

    /**
     * @test-id IT-396
     */
    public function test_IT_396_required_text_field_is_announced_as_required_and_asterisk_is_hidden(): void
    {
        $html = $this->renderField(['name' => 'first_name', 'label' => 'First Name', 'required' => true]);

        $this->assertMatchesRegularExpression('/<input[^>]*id="first_name"[^>]*aria-required="true"/', $html);
        $this->assertStringContainsString('<span class="req" aria-hidden="true">*</span>', $html);
        $this->assertStringNotContainsString(' required=', $html);

        $optional = $this->renderField(['name' => 'company', 'label' => 'Company Name']);

        $this->assertStringNotContainsString('aria-required', $optional);
    }

    /**
     * @test-id IT-396
     */
    public function test_IT_396_select_keeps_screen_reader_label_when_labels_are_hidden(): void
    {
        $html = $this->renderField([
            'name'        => 'country',
            'label'       => 'Country',
            'type'        => 'select',
            'choices'     => ['US' => 'United States'],
            'required'    => true,
            'hide_labels' => 'hide',
        ]);

        $this->assertStringContainsString('<label for="country" class="ppcart-screen-reader-text">Country</label>', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*id="country"[^>]*aria-required="true"/', $html);

        $text = $this->renderField(['name' => 'city', 'label' => 'Town / City', 'hide_labels' => 'hide']);

        $this->assertStringNotContainsString('<label', $text);
    }

    /**
     * @test-id IT-396
     */
    public function test_IT_396_aria_required_survives_frontend_kses(): void
    {
        $sanitized = ppcart_kses_frontend_html('<select name="state" aria-required="true"></select><input name="zip" aria-required="true">');

        $this->assertSame(2, substr_count($sanitized, 'aria-required="true"'));
    }
}
