<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class PayPlanQueryVarTest extends WPTestCase
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

        if (! function_exists('ppcart_checkout_selected_plan')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }

        $this->previousProduct = $GLOBALS['ppcart_product'] ?? null;
    }

    protected function tearDown(): void
    {
        set_query_var('ppcart-pay-plan', '');
        set_query_var('plan', '');

        if (null === $this->previousProduct) {
            unset($GLOBALS['ppcart_product']);
        } else {
            $GLOBALS['ppcart_product'] = $this->previousProduct;
        }

        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }

        $this->createdPostIds = [];

        parent::tearDown();
    }

    /**
     * @test-id IT-371
     */
    public function test_IT_371_query_vars_filter_registers_prefixed_coupon_and_pay_plan(): void
    {
        $qvars = apply_filters('query_vars', []);

        $this->assertContains('ppcart-coupon', $qvars);
        $this->assertContains('ppcart-pay-plan', $qvars);
        $this->assertContains('ppcart-plan', $qvars);
        $this->assertNotContains('coupon', $qvars);
        $this->assertNotContains('plan', $qvars);
    }

    /**
     * @test-id IT-371
     */
    public function test_IT_371_ppcart_pay_plan_query_var_selects_matching_checkout_plan(): void
    {
        $productId = $this->createProductWithPlans();
        $this->hydrateCheckoutProduct($productId);

        set_query_var('ppcart-pay-plan', 'second_plan');
        set_query_var('plan', '');

        $selected = ppcart_checkout_selected_plan($productId);

        $this->assertIsObject($selected);
        $this->assertSame('second_plan', (string) $selected->option_id);

        $html = $this->renderPaymentPlans($productId);
        $this->assertMatchesRegularExpression(
            '/id="option-second_plan"[^>]*\bchecked\b/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="option-first_plan"[^>]*\bchecked\b/',
            $html
        );
    }

    /**
     * @test-id IT-371
     */
    public function test_IT_371_unprefixed_plan_query_var_does_not_select_checkout_plan(): void
    {
        $productId = $this->createProductWithPlans();
        $this->hydrateCheckoutProduct($productId);

        set_query_var('ppcart-pay-plan', '');
        set_query_var('plan', 'second_plan');

        $selected = ppcart_checkout_selected_plan($productId);

        $this->assertIsObject($selected);
        $this->assertSame('first_plan', (string) $selected->option_id);
    }

    private function createProductWithPlans(): int
    {
        $productId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'IT-371 Product ' . wp_generate_uuid4(),
            ]
        );

        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        $this->createdPostIds[] = $productId;

        update_post_meta(
            $productId,
            '_ppcart_pay_options',
            [
                [
                    'option_id'    => 'first_plan',
                    'option_name'  => 'First plan',
                    'price'        => '10',
                    'frequency'    => '1',
                    'interval'     => 'month',
                    'installments' => '-1',
                    'product_type' => 'one-time',
                ],
                [
                    'option_id'    => 'second_plan',
                    'option_name'  => 'Second plan',
                    'price'        => '20',
                    'frequency'    => '1',
                    'interval'     => 'month',
                    'installments' => '-1',
                    'product_type' => 'one-time',
                ],
            ]
        );

        return $productId;
    }

    private function hydrateCheckoutProduct(int $productId): void
    {
        $product = ppcart_setup_product($productId);
        $this->assertIsObject($product);
        $GLOBALS['ppcart_product'] = $product;
    }

    private function renderPaymentPlans(int $productId): string
    {
        ob_start();
        ppcart_payment_plan_options($productId, false, false, false);

        return (string) ob_get_clean();
    }
}
