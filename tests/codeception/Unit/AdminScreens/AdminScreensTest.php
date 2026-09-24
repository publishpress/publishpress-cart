<?php

namespace unit\AdminScreens;

use Codeception\Test\Unit;
use PPCart_Admin_Screens;
use Tests\Support\WordPressStubContext;
use UnitTester;

class AdminScreensTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::setState('screen', null);
        WordPressStubContext::setState('admin_page_parent', '');
        WordPressStubContext::setState('current_post_type', '');

        WordPressStubContext::set(
            'get_current_screen',
            function () {
                return WordPressStubContext::getState('screen');
            }
        );
        WordPressStubContext::set(
            'get_admin_page_parent',
            function () {
                return WordPressStubContext::getState('admin_page_parent', '');
            }
        );
        WordPressStubContext::set(
            'get_post_type',
            function () {
                return WordPressStubContext::getState('current_post_type', '');
            }
        );

        if (! class_exists(PPCart_Admin_Screens::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-admin-screens.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_001_empty_request_with_no_screen_is_not_a_plugin_admin_screen(): void
    {
        $this->resetAdminScreenContext();

        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_002_settings_page_query_is_recognized_as_a_plugin_admin_screen(): void
    {
        $this->resetAdminScreenContext(array('page' => 'ppcart-settings'));

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_003_settings_page_query_is_specifically_detected_as_the_settings_screen(): void
    {
        $this->resetAdminScreenContext(array('page' => 'ppcart-settings'));

        $this->assertTrue(PPCart_Admin_Screens::is_settings_screen());
    }

    public function test_UT_004_settings_hook_suffix_is_detected_from_the_passed_hook_alone(): void
    {
        $this->resetAdminScreenContext();

        $this->assertTrue(PPCart_Admin_Screens::is_settings_screen('ppcart_page_ppcart-settings'));
    }

    public function test_UT_005_dashboard_page_query_uses_the_canonical_ppcart_slug(): void
    {
        $this->resetAdminScreenContext(array('page' => 'ppcart'));

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen());
        $this->assertTrue(PPCart_Admin_Screens::is_dashboard_screen());
        $this->assertSame('ppcart', PPCart_Admin_Screens::menu_slug());
    }

    public function test_UT_006_publishpress_cart_slug_is_not_recognized_as_the_dashboard(): void
    {
        $this->resetAdminScreenContext(array('page' => 'publishpress-cart'));

        $this->assertFalse(PPCart_Admin_Screens::is_dashboard_screen());
    }

    public function test_UT_007_core_wp_admin_dashboard_screen_is_not_a_plugin_screen(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'dashboard',
                'base' => 'dashboard',
            )
        );

        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_008_core_dashboard_stays_excluded_even_with_a_cart_menu_parent(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'dashboard',
                'base' => 'dashboard',
            ),
            'ppcart'
        );

        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_009_foreign_screen_ids_merely_containing_sc_are_not_plugin_screens(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'tools_page_my-sc-tool',
                'base' => 'tools_page_my-sc-tool',
            )
        );

        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_010_ppcart_page_child_screen_prefix_is_recognized(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'ppcart_page_ppcart-future',
                'base' => 'ppcart_page_ppcart-future',
            )
        );

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_011_foreign_screen_ids_containing_ppcart_settings_are_not_the_settings_screen(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'tools_page_my-ppcart-settings-helper',
                'base' => 'tools_page_my-ppcart-settings-helper',
            )
        );

        $this->assertFalse(PPCart_Admin_Screens::is_settings_screen());
    }

    public function test_UT_012_explicit_post_type_sc_product_query_is_a_plugin_screen_without_a_screen_object(): void
    {
        $this->resetAdminScreenContext(array('post_type' => 'ppcart_product'));

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_013_stale_post_type_query_does_not_hijack_an_unrelated_wp_admin_screen(): void
    {
        $this->resetAdminScreenContext(
            array('post_type' => 'sc_product'),
            array(),
            (object) array(
                'id' => 'upload',
                'base' => 'upload',
            )
        );

        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen());
        $this->assertFalse(PPCart_Admin_Screens::is_post_type_screen('sc_product'));
    }

    public function test_UT_014_single_product_editor_is_a_plugin_screen(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'ppcart_product',
                'base' => 'post',
                'post_type' => 'ppcart_product',
            )
        );

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_015_single_product_editor_is_excluded_from_version_notices(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'ppcart_product',
                'base' => 'post',
                'post_type' => 'ppcart_product',
            )
        );

        $this->assertFalse(PPCart_Admin_Screens::is_version_notice_screen());
    }

    public function test_UT_015_canonical_product_editor_is_excluded_from_version_notices(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'ppcart_product',
                'base' => 'post',
                'post_type' => 'ppcart_product',
            )
        );

        $this->assertFalse(PPCart_Admin_Screens::is_version_notice_screen());
    }

    public function test_UT_016_single_order_editor_remains_eligible_for_version_notices(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'ppcart_order',
                'base' => 'post',
                'post_type' => 'ppcart_order',
            )
        );

        $this->assertTrue(PPCart_Admin_Screens::is_version_notice_screen());
    }

    public function test_UT_017_standalone_stripe_webhook_log_route_is_a_plugin_admin_request(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array('ppcart_view_stripe_webhook_log' => 1)
        );

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_018_standalone_log_viewer_route_is_exposed_by_a_dedicated_check(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array('ppcart_view_stripe_webhook_log' => 1)
        );

        $this->assertTrue(PPCart_Admin_Screens::is_standalone_log_viewer_route());
    }

    public function test_UT_019_canonical_ppcart_page_submenu_hook_passed_as_argument_is_recognized(): void
    {
        $this->resetAdminScreenContext();

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen('ppcart_page_ppcart-future'));
    }

    public function test_UT_020_detection_re_reads_the_current_screen_on_every_call(): void
    {
        $this->resetAdminScreenContext();
        $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen());

        WordPressStubContext::setState(
            'screen',
            (object) array(
                'id' => 'ppcart_page_ppcart-settings',
                'base' => 'ppcart_page_ppcart-settings',
            )
        );

        $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen());
    }

    public function test_UT_021_contacts_page_is_covered_by_the_order_contact_customer_report_ui_asset_group(): void
    {
        $this->resetAdminScreenContext(array('page' => 'ppcart-contacts'));

        $this->assertTrue(PPCart_Admin_Screens::is_order_contact_or_customer_report_screen());
    }

    public function test_UT_021_canonical_order_and_product_screens_use_modern_admin_ui(): void
    {
        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'ppcart_order',
                'base' => 'post',
                'post_type' => 'ppcart_order',
            )
        );

        $this->assertTrue(PPCart_Admin_Screens::is_modern_admin_ui_screen());
        $this->assertTrue(PPCart_Admin_Screens::is_order_contact_or_customer_report_screen());

        $this->resetAdminScreenContext(
            array(),
            array(),
            (object) array(
                'id' => 'ppcart_product',
                'base' => 'post',
                'post_type' => 'ppcart_product',
            )
        );

        $this->assertTrue(PPCart_Admin_Screens::is_modern_admin_ui_screen());
    }

    /**
     * @test-id UT-197
     */
    public function test_UT_197_exposes_the_complete_canonical_admin_page_and_hook_contract(): void
    {
        $page_slugs = [
            'ppcart',
            'ppcart-settings',
            'ppcart-reports',
            'ppcart-customer-reports',
            'ppcart-contacts',
            'ppcart-extensions',
            'ppcart-white-label',
            'ppcart-affiliates',
        ];
        $hook_suffixes = [
            'toplevel_page_ppcart',
            'ppcart_page_ppcart-settings',
            'ppcart_page_ppcart-reports',
            'ppcart_page_ppcart-customer-reports',
            'ppcart_page_ppcart-contacts',
            'ppcart_page_ppcart-extensions',
            'ppcart_page_ppcart-white-label',
            'ppcart_page_ppcart-affiliates',
        ];

        $this->assertSame($page_slugs, PPCart_Admin_Screens::PAGE_SLUGS);
        $this->assertSame($hook_suffixes, PPCart_Admin_Screens::HOOK_SUFFIXES);
        $this->assertSame('ppcart', PPCart_Admin_Screens::menu_slug());
        $this->assertSame('ppcart_page_', PPCart_Admin_Screens::MENU_HOOK_PREFIX);
        $this->assertSame(['ppcart-'], PPCart_Admin_Screens::COMPATIBLE_PAGE_PREFIXES);
        $this->assertSame(['ppcart_page_'], PPCart_Admin_Screens::COMPATIBLE_HOOK_PREFIXES);

        foreach ($page_slugs as $page_slug) {
            $this->resetAdminScreenContext(['page' => $page_slug]);
            $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen(), $page_slug);
        }

        foreach ($hook_suffixes as $hook_suffix) {
            $this->resetAdminScreenContext();
            $this->assertTrue(PPCart_Admin_Screens::is_plugin_screen($hook_suffix), $hook_suffix);
        }

        foreach (array_slice($page_slugs, 1) as $index => $page_slug) {
            $this->assertSame($hook_suffixes[ $index + 1 ], PPCart_Admin_Screens::submenu_hook_suffix($page_slug));
        }
    }

    /**
     * @test-id UT-197
     */
    public function test_UT_197_rejects_all_legacy_admin_page_and_hook_identifiers(): void
    {
        $legacy_page_slugs = [
            'studiocart',
            'sc-admin',
            'ncs-cart-reports',
            'ncs-cart-customer-reports',
            'ncs-cart-contacts-page',
            'ncs-cart-extensions-page',
            'sc-white-label',
            'sc_affiliate_dashboard_callback',
            'sc-future',
            'ncs-cart-future',
        ];
        $legacy_hook_suffixes = [
            'toplevel_page_studiocart',
            'studiocart_page_sc-admin',
            'studiocart_page_ncs-cart-reports',
            'studiocart_page_ncs-cart-customer-reports',
            'studiocart_page_ncs-cart-contacts-page',
            'studiocart_page_ncs-cart-extensions-page',
            'studiocart_page_sc-white-label',
            'studiocart_page_sc_affiliate_dashboard_callback',
            'studiocart_page_sc-future',
        ];

        foreach ($legacy_page_slugs as $page_slug) {
            $this->resetAdminScreenContext(['page' => $page_slug]);
            $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen(), $page_slug);
        }

        foreach ($legacy_hook_suffixes as $hook_suffix) {
            $this->resetAdminScreenContext();
            $this->assertFalse(PPCart_Admin_Screens::is_plugin_screen($hook_suffix), $hook_suffix);
        }
    }

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $request
     * @param object|null          $screen
     * @param string               $parent
     * @param string               $post_type
     * @return void
     */
    private function resetAdminScreenContext(
        array $get = array(),
        array $request = array(),
        $screen = null,
        string $parent = '',
        string $post_type = ''
    ): void {
        $_GET = $get;
        $_POST = $request;
        $_REQUEST = array_merge($get, $request);

        WordPressStubContext::setState('screen', $screen);
        WordPressStubContext::setState('admin_page_parent', $parent);
        WordPressStubContext::setState('current_post_type', $post_type);

        PPCart_Admin_Screens::reset_cache();
    }
}
