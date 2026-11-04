<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;

class ProductOptionSourcesTest extends WPTestCase
{
    protected $tester;

    private function optionsFor($post_id = null): array
    {
        $previous_get = $_GET;
        unset($_GET['post']);
        if (null !== $post_id) {
            $_GET['post'] = (string) $post_id;
        }
        try {
            return \PPCart_Product_Metabox_Option_Sources::product_options();
        } finally {
            $_GET = $previous_get;
        }
    }

    public function testNewProductCanSelectPublishedProducts(): void
    {
        $product_id = self::factory()->post->create([
            'post_type' => ppcart_live_post_type('product'),
            'post_status' => 'publish',
            'post_title' => 'Checkout offer',
        ]);

        $options = $this->optionsFor();
        $this->assertArrayHasKey($product_id, $options);
        $this->assertSame('Checkout offer (ID: ' . $product_id . ')', $options[$product_id]);
    }

    public function testEditedProductIsExcludedButOtherProductsRemainAvailable(): void
    {
        $product_id = self::factory()->post->create([
            'post_type' => ppcart_live_post_type('product'),
            'post_status' => 'publish',
        ]);
        $other_id = self::factory()->post->create([
            'post_type' => ppcart_live_post_type('product'),
            'post_status' => 'publish',
        ]);

        $options = $this->optionsFor($product_id);
        $this->assertArrayNotHasKey($product_id, $options);
        $this->assertArrayHasKey($other_id, $options);
    }

    public function testDraftProductsAreNotOfferedOnNewProducts(): void
    {
        $draft_id = self::factory()->post->create([
            'post_type' => ppcart_live_post_type('product'),
            'post_status' => 'draft',
        ]);

        $this->assertArrayNotHasKey($draft_id, $this->optionsFor());
    }
}
