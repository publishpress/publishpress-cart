<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class CheckoutRadioGroupsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var list<int>
     */
    private $createdPostIds = [];

    /**
     * @var mixed
     */
    private $previousProduct;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('ppcart_payment_plan_options')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }

        $this->previousProduct = $GLOBALS['ppcart_product'] ?? null;
    }

    protected function tearDown(): void
    {
        if (null === $this->previousProduct) {
            unset($GLOBALS['ppcart_product']);
        } else {
            $GLOBALS['ppcart_product'] = $this->previousProduct;
        }

        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }

        $this->createdPostIds = [];
        delete_option('_ppcart_cashondelivery_enable');

        parent::tearDown();
    }

    /**
     * @test-id IT-414
     */
    public function test_IT_414_payment_plan_radios_are_grouped_and_named_by_heading(): void
    {
        $productId = $this->createProductWithPlans();
        $GLOBALS['ppcart_product'] = ppcart_setup_product($productId);

        ob_start();
        ppcart_payment_plan_options($productId, false, false, false);
        $html = (string) ob_get_clean();

        $this->assertMatchesRegularExpression('/<h3 class="title" id="([^"]+)">/', $html);
        preg_match('/<h3 class="title" id="([^"]+)">/', $html, $heading);
        preg_match('/<fieldset[^>]*aria-labelledby="([^"]+)"[^>]*>(.*?)<\/fieldset>/s', $html, $group);

        $this->assertNotEmpty($group, 'Payment plans are not wrapped in a fieldset.');
        $this->assertSame($heading[1], $group[1]);
        $this->assertSame(2, substr_count($group[2], 'name="ppcart_product_option"'));
    }

    /**
     * @test-id IT-414
     */
    public function test_IT_414_payment_method_radios_are_grouped_and_named_by_heading(): void
    {
        update_option('_ppcart_cashondelivery_enable', '1');

        ob_start();
        ppcart_do_payment_methods(0);
        $html = (string) ob_get_clean();

        preg_match('/<h3 class="title" id="([^"]+)">/', $html, $heading);
        preg_match('/<fieldset class="pay-methods[^"]*" aria-labelledby="([^"]+)">(.*?)<\/fieldset>/s', $html, $group);

        $this->assertNotEmpty($heading);
        $this->assertNotEmpty($group, 'Payment methods are not wrapped in a fieldset.');
        $this->assertSame($heading[1], $group[1]);
        $this->assertStringContainsString('name="pay-method"', $group[2]);
    }

    /**
     * @test-id IT-414
     */
    public function test_IT_414_frontend_kses_keeps_fieldset_group_name(): void
    {
        $sanitized = ppcart_kses_frontend_html('<fieldset class="pay-methods" aria-labelledby="h1"><input type="radio" name="pay-method"></fieldset>');

        $this->assertStringContainsString('aria-labelledby="h1"', $sanitized);
    }

    private function createProductWithPlans(): int
    {
        $productId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'IT-414 Product ' . wp_generate_uuid4(),
            ]
        );

        $this->assertIsInt($productId);
        $this->createdPostIds[] = $productId;

        update_post_meta(
            $productId,
            '_ppcart_pay_options',
            [
                ['option_id' => 'first_plan', 'option_name' => 'First plan', 'price' => '10', 'frequency' => '1', 'interval' => 'month', 'installments' => '-1', 'product_type' => 'one-time'],
                ['option_id' => 'second_plan', 'option_name' => 'Second plan', 'price' => '20', 'frequency' => '1', 'interval' => 'month', 'installments' => '-1', 'product_type' => 'one-time'],
            ]
        );

        return $productId;
    }
}
