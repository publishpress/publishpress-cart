<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Product_Metaboxes;
use ReflectionMethod;
use ReflectionProperty;

class ProductIntegrationHeadingEscapeTest extends WPTestCase
{
    /**
     * @var int[]
     */
    private $createdPostIds = [];

    /**
     * @var array<string, mixed>
     */
    private $previousGet = [];

    protected function tearDown(): void
    {
        $_GET = $this->previousGet;

        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }

        $this->createdPostIds = [];

        parent::tearDown();
    }

    /**
     * The tab no longer prints the product title in a heading. Keep the
     * GHSA-w7xm-9m54-hrqv guard: no HTML item in the tab may print the raw title.
     *
     * @test-id IT-389
     */
    public function test_IT_389_integration_fields_never_print_the_raw_product_title(): void
    {
        $this->previousGet = $_GET;

        $maliciousTitle = '<a href="https://evil.example">phish</a>';
        $productId = wp_insert_post(
            [
                'post_type' => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title' => $maliciousTitle,
            ]
        );

        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        $this->createdPostIds[] = $productId;

        $_GET['post'] = (string) $productId;

        $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');

        $setFieldGroups = new ReflectionMethod(PPCart_Product_Metaboxes::class, 'set_field_groups');
        $setFieldGroups->setAccessible(true);
        $setFieldGroups->invoke($metaboxes);

        $integrationsProperty = new ReflectionProperty(PPCart_Product_Metaboxes::class, 'integrations');
        $integrationsProperty->setAccessible(true);
        $integrations = $integrationsProperty->getValue($metaboxes);

        $this->assertIsArray($integrations);

        $htmlFields = array_values(array_filter(
            $integrations,
            static function ($field) {
                return is_array($field) && ($field['type'] ?? '') === 'html';
            }
        ));

        $this->assertNotEmpty($htmlFields);

        $renderFields = new ReflectionMethod(PPCart_Product_Metaboxes::class, 'metabox_fields');
        $renderFields->setAccessible(true);

        ob_start();
        $renderFields->invoke($metaboxes, $htmlFields);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('ppcart-renew-lists', $html);
        $this->assertStringNotContainsString('<a href="https://evil.example">', $html);
    }
}
