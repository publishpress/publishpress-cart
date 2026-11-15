<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class CheckoutAutocompleteTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var mixed
     */
    private $previousProduct;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('ppcart_do_checkoutform_fields')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }

        $this->previousProduct = $GLOBALS['ppcart_product'] ?? null;
        $GLOBALS['ppcart_product'] = (object) ['ID' => 0, 'default_fields' => true];
    }

    protected function tearDown(): void
    {
        if (null === $this->previousProduct) {
            unset($GLOBALS['ppcart_product']);
        } else {
            $GLOBALS['ppcart_product'] = $this->previousProduct;
        }

        parent::tearDown();
    }

    private function assertFieldAutocomplete(string $html, string $tag, string $name, string $token): void
    {
        $this->assertMatchesRegularExpression(
            '/<' . $tag . '[^>]*name="' . preg_quote($name, '/') . '"[^>]*autocomplete="' . preg_quote($token, '/') . '"/',
            $html,
            "{$name} should have autocomplete=\"{$token}\"."
        );
    }

    /**
     * @test-id IT-398
     */
    public function test_IT_398_contact_fields_carry_autocomplete_tokens(): void
    {
        ob_start();
        ppcart_do_checkoutform_fields(0, false);
        $html = (string) ob_get_clean();

        $this->assertFieldAutocomplete($html, 'input', 'first_name', 'given-name');
        $this->assertFieldAutocomplete($html, 'input', 'last_name', 'family-name');
        $this->assertFieldAutocomplete($html, 'input', 'email', 'email');
        $this->assertFieldAutocomplete($html, 'input', 'phone', 'tel');
        $this->assertFieldAutocomplete($html, 'input', 'company', 'organization');
    }

    /**
     * @test-id IT-398
     */
    public function test_IT_398_address_fields_carry_autocomplete_tokens(): void
    {
        ob_start();
        ppcart_address_fields(0, false);
        $html = (string) ob_get_clean();

        $this->assertFieldAutocomplete($html, 'select', 'country', 'country');
        $this->assertFieldAutocomplete($html, 'input', 'address1', 'address-line1');
        $this->assertFieldAutocomplete($html, 'input', 'address2', 'address-line2');
        $this->assertFieldAutocomplete($html, 'input', 'city', 'address-level2');
        $this->assertFieldAutocomplete($html, 'select', 'state', 'address-level1');
        $this->assertFieldAutocomplete($html, 'input', 'zip', 'postal-code');
    }

    /**
     * @test-id IT-398
     */
    public function test_IT_398_frontend_kses_keeps_select_autocomplete(): void
    {
        $sanitized = ppcart_kses_frontend_html('<select name="country" autocomplete="country"></select>');

        $this->assertStringContainsString('autocomplete="country"', $sanitized);
    }
}
