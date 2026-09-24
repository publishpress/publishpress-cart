<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;

class RequestFieldsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-317
     */
    public function test_IT_317_first_party_checkout_emits_and_reads_canonical_request_field_names(): void
    {
        $checkout = (string) file_get_contents(
            PPCART_PLUGIN_ROOT . 'public/templates/functions/payment-address.php'
        );

        $this->assertStringContainsString('name="ppcart-nonce"', $checkout);
        $this->assertStringNotContainsString('name="sc-nonce"', $checkout);
        $this->assertStringContainsString('name="ppcart_product_id"', $checkout);
        $this->assertStringNotContainsString('name="sc_product_id"', $checkout);

        $this->assertTrue(function_exists('ppcart_filter_input'));
        $this->assertTrue(function_exists('ppcart_filter_input_array'));
    }
}
