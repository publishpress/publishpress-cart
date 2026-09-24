<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Product_Metaboxes;
use ReflectionMethod;

class HtmlIdsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var mixed
     */
    private $previousMetaBoxes;

    /**
     * @var bool
     */
    private $hadMetaBoxes = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('add_meta_box')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $this->hadMetaBoxes = array_key_exists('wp_meta_boxes', $GLOBALS);
        $this->previousMetaBoxes = $GLOBALS['wp_meta_boxes'] ?? null;
        $GLOBALS['wp_meta_boxes'] = [];
    }

    protected function tearDown(): void
    {
        if ($this->hadMetaBoxes) {
            $GLOBALS['wp_meta_boxes'] = $this->previousMetaBoxes;
        } else {
            unset($GLOBALS['wp_meta_boxes']);
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-319
     */
    public function test_IT_319_product_metabox_registers_canonical_id(): void
    {
        $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
        $metaboxes->add_metaboxes();

        $serialized = wp_json_encode($GLOBALS['wp_meta_boxes']);
        $this->assertIsString($serialized);
        $this->assertStringContainsString('ppcart-product-settings', $serialized);
        $this->assertStringNotContainsString('sc-product-settings', $serialized);
    }

    /**
     * @test-id IT-319
     */
    public function test_IT_319_metabox_field_wrapper_emits_canonical_html_ids(): void
    {
        $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
        $method = new ReflectionMethod(PPCart_Product_Metaboxes::class, 'metabox_fields');
        $method->setAccessible(true);

        ob_start();
        $method->invoke(
            $metaboxes,
            [
                [
                    'type' => 'checkbox',
                    'id' => '_ppcart_on_sale',
                    'label' => 'On sale',
                    'value' => '',
                    'class' => '',
                    'class_size' => '',
                    'description' => '',
                ],
            ]
        );
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('id="rid_ppcart_on_sale"', $html);
        $this->assertStringContainsString('id="_ppcart_on_sale"', $html);
        $this->assertStringNotContainsString('rid_sc_', $html);
        $this->assertStringNotContainsString('id="_sc_on_sale"', $html);
    }
}
