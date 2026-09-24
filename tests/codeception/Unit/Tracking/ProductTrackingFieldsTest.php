<?php

namespace unit\Tracking;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-feature-support.php';
require_once PPCART_PLUGIN_ROOT . 'admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php';

final class ProductSettingTabsHarness
{
    use \PPCart_Product_Metaboxes_Render_Trait;

    /**
     * @return array<string, string>
     */
    public function tabs()
    {
        return $this->get_product_setting_tabs();
    }

    /**
     * @param string $context
     * @return array<int, string>
     */
    public function groups($context = 'render')
    {
        return $this->get_product_field_groups($context);
    }
}

class ProductTrackingFieldsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var ProductSettingTabsHarness
     */
    private $harness;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook, $value) {
                return $value;
            }
        );

        $this->harness = new ProductSettingTabsHarness();
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-325
     */
    public function test_UT_325_product_tabs_and_field_groups_omit_tracking(): void
    {
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook, $value) {
                if ('ppcart_supports_feature' === $hook) {
                    return false;
                }

                return $value;
            }
        );
        $this->assertTabsAndGroupsOmitTracking();

        WordPressStubContext::set(
            'apply_filters',
            static function ($hook, $value) {
                if ('ppcart_supports_feature' === $hook) {
                    return true;
                }

                return $value;
            }
        );
        $this->assertTabsAndGroupsOmitTracking();
    }

    /**
     * @return void
     */
    private function assertTabsAndGroupsOmitTracking(): void
    {
        $tabs = $this->harness->tabs();
        $this->assertIsArray($tabs);
        $this->assertArrayNotHasKey('tracking', $tabs);

        foreach (['render', 'save'] as $context) {
            $groups = $this->harness->groups($context);
            $this->assertIsArray($groups);
            $this->assertNotContains('tracking', $groups);
        }
    }
}
