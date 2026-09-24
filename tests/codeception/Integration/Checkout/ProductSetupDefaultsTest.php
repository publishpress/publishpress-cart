<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class ProductSetupDefaultsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var list<int>
     */
    private $createdPostIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }

        $this->createdPostIds = [];

        parent::tearDown();
    }

    /**
     * @test-id IT-279
     */
    public function test_IT_279_product_without_plan_or_button_meta_gets_empty_plans_and_default_button_text(): void
    {
        $productId = $this->createProduct();

        $product = ppcart_setup_product($productId);

        $this->assertIsObject($product);
        $this->assertSame([], $product->pay_options);
        $this->assertSame('Order Now', $product->button_text);
    }

    /**
     * @test-id IT-279
     */
    public function test_IT_279_canonical_ppcart_pay_options_and_button_text_hydrate_the_product_object(): void
    {
        $payOptions = [
            [
                'option_id'   => 'plan_one',
                'option_name' => 'One-time',
                'price'       => '25',
                'frequency'   => '1',
                'interval'    => 'month',
                'installments'=> '-1',
            ],
        ];
        $productId = $this->createProduct();
        update_post_meta($productId, '_ppcart_pay_options', $payOptions);
        update_post_meta($productId, '_ppcart_button_text', 'Buy Instantly');

        $product = ppcart_setup_product($productId);

        $this->assertIsObject($product);
        $this->assertSame($payOptions, $product->pay_options);
        $this->assertSame('Buy Instantly', $product->button_text);
    }

    /**
     * @test-id IT-279
     */
    public function test_IT_279_null_pay_options_render_plans_and_submit_button_without_php_warnings(): void
    {
        $productId = $this->createProduct();
        update_post_meta($productId, '_ppcart_pay_options', null);

        $product = ppcart_setup_product($productId);

        $this->assertIsObject($product);
        $this->assertIsArray($product->pay_options);
        $this->assertSame('Order Now', $product->button_text);

        if (! function_exists('ppcart_payment_plan_options')) {
            require_once PPCART_BASE_DIR . 'public/templates/template-functions.php';
        }

        $previousProduct = $GLOBALS['ppcart_product'] ?? null;
        $warnings        = [];
        try {
            $warnings = $this->collectPhpWarnings(
                function () use ($product, $productId) {
                    $GLOBALS['ppcart_product'] = $product;

                    ob_start();
                    ppcart_payment_plan_options($productId, false);
                    ppcart_helper()->renderTemplate(
                        'order-form/submit-button',
                        [
                            'ppcart_product' => $product,
                            'id'             => 'ppcart_card_button',
                            'ppcart_uid'     => 'ppcart_test',
                        ]
                    );
                    ob_get_clean();
                }
            );
        } finally {
            if (null === $previousProduct) {
                unset($GLOBALS['ppcart_product']);
            } else {
                $GLOBALS['ppcart_product'] = $previousProduct;
            }
        }

        foreach ($warnings as $warning) {
            $this->assertStringNotContainsString('foreach() argument must be of type array|object', $warning);
            $this->assertStringNotContainsString('Undefined property: stdClass::$button_text', $warning);
        }
    }

    private function createProduct(): int
    {
        $productId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'IT-279 Product ' . wp_generate_uuid4(),
            ]
        );

        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        $this->createdPostIds[] = $productId;

        return $productId;
    }

    /**
     * @param callable(): void $callback
     * @return list<string>
     */
    private function collectPhpWarnings(callable $callback): array
    {
        $warnings = [];
        set_error_handler(
            static function ($errno, $errstr) use (&$warnings) {
                $warnings[] = (string) $errstr;

                return true;
            },
            E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE
        );

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $warnings;
    }
}
