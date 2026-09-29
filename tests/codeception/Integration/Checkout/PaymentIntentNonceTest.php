<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Public_Payment_Controller;

/**
 * The public "create payment intent" AJAX handler checks the checkout nonce first.
 *
 * The handler reads the nonce with filter_input(), which does not see $_POST in a
 * CLI test run, so only the rejection path can be driven from here. The accepted
 * path is covered by the checkout regression suite.
 */
class PaymentIntentNonceTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function tearDown(): void
    {
        $_POST    = [];
        $_REQUEST = [];
        wp_set_current_user(0);

        parent::tearDown();
    }

    /**
     * @test-id IT-389
     */
    public function test_IT_389_guest_request_without_checkout_nonce_is_rejected(): void
    {
        $response = $this->requestPaymentIntent([ 'ppcart_product_id' => '1' ]);

        $this->assertFalse($response['success']);
        $this->assertSame('Invalid Request', $response['data']['error']);
    }

    /**
     * @return callable
     */
    public function getAjaxDieHandler(): callable
    {
        return static function ($message = '', $title = '', $args = []): void {
            throw new \RuntimeException('wp_die');
        };
    }

    /**
     * @param array<string, string> $post Request fields.
     * @return array<string, mixed>
     */
    private function requestPaymentIntent(array $post): array
    {
        wp_set_current_user(0);
        $_POST    = $post;
        $_REQUEST = $post;

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);

        ob_start();
        try {
            ( new PPCart_Public_Payment_Controller() )->create_payment_intent();
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        } finally {
            remove_filter('wp_doing_ajax', '__return_true');
            remove_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);
        }

        $response = json_decode(trim((string) ob_get_clean()), true);
        $this->assertIsArray($response);

        return $response;
    }
}
