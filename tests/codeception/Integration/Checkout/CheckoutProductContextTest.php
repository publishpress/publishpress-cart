<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class CheckoutProductContextTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var int
     */
    protected $createdProductId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('ppcart_checkout_product_context')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdProductId) {
            wp_delete_post($this->createdProductId, true);
            $this->createdProductId = 0;
        }

        parent::tearDown();
    }

    public function test_IT_320_product_query_var_slug_does_not_skip_checkout_product_setup(): void
    {
        $productId = wp_insert_post(
            array(
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'Product 1',
                'post_name'   => 'product-1',
            )
        );

        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        $this->createdProductId = (int) $productId;

        update_post_meta(
            $this->createdProductId,
            '_ppcart_pay_options',
            array(
                array(
                    'option_id'   => 'product_1_plan',
                    'option_name' => 'Product 1 Plan',
                    'price'       => '10',
                ),
            )
        );

        $post = get_post($this->createdProductId);
        $this->assertInstanceOf(\WP_Post::class, $post);

        $previousProduct = isset($GLOBALS['ppcart_product']) ? $GLOBALS['ppcart_product'] : null;
        $previousPost    = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;

        $GLOBALS['ppcart_product'] = $post->post_name;
        $GLOBALS['post']           = $post;

        try {
            $product = ppcart_checkout_product_context($post->ID);

            $this->assertIsObject($product);
            $this->assertTrue(isset($product->ID));
            $this->assertSame($this->createdProductId, (int) $product->ID);
            $this->assertNotSame($post->post_name, $product->ID);
        } finally {
            if (null === $previousProduct) {
                unset($GLOBALS['ppcart_product']);
            } else {
                $GLOBALS['ppcart_product'] = $previousProduct;
            }

            if (null === $previousPost) {
                unset($GLOBALS['post']);
            } else {
                $GLOBALS['post'] = $previousPost;
            }
        }
    }
}
