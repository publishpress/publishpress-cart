<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Screens;
use PPCart_Product_Template;

class CptTaxonomyStringsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-328
     */
    public function test_IT_328_admin_screen_constants_are_canonical(): void
    {
        $this->assertSame('ppcart_collection', PPCart_Admin_Screens::POST_TYPE_COLLECTION);
        $this->assertSame('ppcart_us_path', PPCart_Admin_Screens::POST_TYPE_UPSELL_PATH);
        $this->assertSame('ppcart_membership', PPCart_Admin_Screens::POST_TYPE_MEMBERSHIP);
        $this->assertSame('ppcart_upgrade_path', PPCart_Admin_Screens::POST_TYPE_UPGRADE_PATH);
        $this->assertSame('ppcart_product_cat', PPCart_Admin_Screens::TAXONOMY_PRODUCT_CATEGORY);
        $this->assertSame('single-ppcart_product', PPCart_Product_Template::TEMPLATE_SLUG);
    }
}
