<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;

/**
 * Behavior checks for code that no longer uses extract() or unrestricted unserialize().
 */
class ScannerFunctionHardeningTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var int[]
     */
    private $createdPostIds = [];

    public function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }

        $this->createdPostIds = [];
        parent::tearDown();
    }

    public function test_do_field_renders_select_with_name_label_and_choices(): void
    {
        $this->loadFieldFunctions();

        ob_start();
        ppcart_do_field([
            'name'     => 'ppcart_test_select',
            'label'    => 'Pick one',
            'type'     => 'select',
            'required' => true,
            'choices'  => [ 'a' => 'Alpha', 'b' => 'Beta' ],
            'value'    => 'b',
        ]);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('<label for="ppcart_test_select">Pick one <span class="req">*</span></label>', $html);
        $this->assertStringContainsString('name="ppcart_test_select"', $html);
        $this->assertStringContainsString('class="ppcart-form-control  required"', $html);
        $this->assertStringContainsString('<option value="b" ' . selected('b', 'b', false) . '>Beta</option>', $html);
        $this->assertStringContainsString('<option value="a" >Alpha</option>', $html);
    }

    public function test_do_field_text_input_uses_defaults_and_ignores_unknown_keys(): void
    {
        $this->loadFieldFunctions();

        ob_start();
        ppcart_do_field([
            'name'   => 'ppcart_test_text',
            'label'  => 'Your name',
            'cols'   => 12,
            // Unknown keys must not become local variables that change the output.
            'type_override' => 'hidden',
            'posted_ppcart_errors' => [ 'ppcart_test_text' => 'forged' ],
        ]);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('ppcart-col-sm-12', $html);
        $this->assertStringContainsString('id="ppcart_test_text"', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('placeholder="Your name"', $html);
        $this->assertStringNotContainsString('forged', $html);
        $this->assertStringNotContainsString('invalid', $html);
    }

    public function test_product_detail_shortcode_renders_name_and_rejects_unknown_fields(): void
    {
        $productId = wp_insert_post([
            'post_type'   => ppcart_live_post_type('product'),
            'post_status' => 'publish',
            'post_title'  => 'Scanner Product',
        ]);
        $this->assertIsInt($productId);
        $this->createdPostIds[] = (int) $productId;

        $this->assertSame(
            esc_html(ppcart_get_public_product_name($productId)),
            do_shortcode('[ppcart_product id="' . $productId . '"]')
        );
        $this->assertSame('', do_shortcode('[ppcart_product id="' . $productId . '" field="post_content"]'));
    }

    public function test_unserialize_plain_object_allows_only_stdclass(): void
    {
        $plan = (object) [ 'price' => 10, 'nested' => (object) [ 'a' => [ 1, 2 ] ] ];

        $this->assertEquals($plan, ppcart_unserialize_plain_object(serialize($plan)));
        $this->assertNull(ppcart_unserialize_plain_object(serialize(new \ArrayObject([ 1 ]))));
        $this->assertNull(ppcart_unserialize_plain_object(serialize((object) [ 'inner' => new \ArrayObject([ 1 ]) ])));
        $this->assertNull(ppcart_unserialize_plain_object(serialize([ 'not' => 'an object' ])));
        $this->assertNull(ppcart_unserialize_plain_object('not serialized'));
        $this->assertNull(ppcart_unserialize_plain_object(''));
        $this->assertNull(ppcart_unserialize_plain_object([ 'array' ]));
    }

    public function test_order_rehydrates_double_serialized_stdclass_tax_data_but_not_other_classes(): void
    {
        $orderId = $this->createOrder();
        ppcart_update_post_meta($orderId, 'tax_data', serialize((object) [ 'type' => 'inclusive', 'rate' => 5 ]));

        $order = new PPCart_Order($orderId);
        $this->assertInstanceOf(\stdClass::class, $order->tax_data);
        $this->assertSame('inclusive', $order->tax_data->type);

        $hostile = serialize(new \ArrayObject([ 'x' ]));
        ppcart_update_post_meta($orderId, 'tax_data', $hostile);

        $order = new PPCart_Order($orderId);
        $this->assertNotInstanceOf(\ArrayObject::class, $order->tax_data);
        $this->assertSame($hostile, $order->tax_data);
    }

    private function createOrder(): int
    {
        $orderId = wp_insert_post([
            'post_type'   => ppcart_live_post_type('order'),
            'post_status' => 'publish',
            'post_title'  => 'Scanner Order',
        ]);
        $this->assertIsInt($orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'status', 'paid');

        return (int) $orderId;
    }

    private function loadFieldFunctions(): void
    {
        if (! function_exists('ppcart_do_field')) {
            require_once PPCART_BASE_DIR . 'public/templates/template-functions.php';
        }

        $this->assertTrue(function_exists('ppcart_do_field'));
    }
}
