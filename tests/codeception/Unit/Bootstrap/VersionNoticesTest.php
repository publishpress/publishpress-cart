<?php

namespace unit\Bootstrap;

use Codeception\Test\Unit;
use PPCart_Admin_Screens;
use PPCart_Version_Notices;
use Tests\Support\WordPressStubContext;
use UnitTester;

class VersionNoticesTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        if (! defined('PPCART_BASE_URL')) {
            define('PPCART_BASE_URL', 'https://example.test/wp-content/plugins/publishpress-cart/');
        }
        if (! defined('PPCART_VERSION')) {
            define('PPCART_VERSION', 'test-version');
        }

        WordPressStubContext::clear();
        WordPressStubContext::setState('filters', []);
        WordPressStubContext::setState('filter_overrides', []);
        WordPressStubContext::setState('caps', ['install_plugins']);
        WordPressStubContext::setState('user_meta', []);
        WordPressStubContext::setState('current_user_id', 1);
        WordPressStubContext::setState('screen', null);

        WordPressStubContext::set(
            'apply_filters',
            static function ($hook, $value) {
                $overrides = WordPressStubContext::getState('filter_overrides', []);

                return array_key_exists($hook, $overrides) ? $overrides[$hook] : $value;
            }
        );
        WordPressStubContext::set(
            'current_user_can',
            static function ($capability) {
                $caps = WordPressStubContext::getState('caps', []);

                return in_array($capability, $caps, true);
            }
        );
        WordPressStubContext::set(
            'add_filter',
            static function ($hook_name, $callback, $priority = 10, $accepted_args = 1) {
                $filters = WordPressStubContext::getState('filters', []);
                $filters[] = $hook_name;
                WordPressStubContext::setState('filters', $filters);

                return true;
            }
        );
        WordPressStubContext::set(
            'get_current_user_id',
            static function () {
                return (int) WordPressStubContext::getState('current_user_id', 0);
            }
        );
        WordPressStubContext::set(
            'get_user_meta',
            static function ($user_id, $key = '', $single = false) {
                $meta = WordPressStubContext::getState('user_meta', []);

                if (! isset($meta[ $user_id ][ $key ])) {
                    return $single ? '' : [];
                }

                $value = $meta[ $user_id ][ $key ];

                return $single ? $value : [ $value ];
            }
        );
        WordPressStubContext::set(
            'get_current_screen',
            static function () {
                return WordPressStubContext::getState('screen');
            }
        );
        WordPressStubContext::set(
            'get_admin_page_parent',
            static function () {
                return '';
            }
        );
        WordPressStubContext::set(
            'get_post_type',
            static function () {
                return '';
            }
        );

        if (! class_exists('PPCart_Admin_Screens', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-admin-screens.php';
        }
        if (! class_exists('PPCart_Version_Notices', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-version-notices.php';
        }
    }

    protected function _after(): void
    {
        unset($_GET['page']);
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-329
     */
    public function test_UT_329_filter_false_skips_version_notice_registration(): void
    {
        WordPressStubContext::setState('filter_overrides', [
            'ppcart_show_version_notices' => false,
        ]);

        PPCart_Version_Notices::register_notice_settings();

        $this->assertSame([], WordPressStubContext::getState('filters', []));
    }

    /**
     * @test-id UT-329
     */
    public function test_UT_329_default_registers_version_notice_filters(): void
    {
        PPCart_Version_Notices::register_notice_settings();

        $this->assertSame(
            [
                'pp_version_notice_menu_link_settings',
            ],
            WordPressStubContext::getState('filters', [])
        );
    }

    /**
     * @test-id UT-329
     */
    public function test_UT_329_registers_banner_styles_with_admin_enqueue_scripts(): void
    {
        $actions = [];

        WordPressStubContext::set(
            'is_admin',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_action',
            static function ($hook_name, $callback, $priority = 10) use (&$actions) {
                $actions[] = [$hook_name, $callback, $priority];

                return true;
            }
        );

        PPCart_Version_Notices::init();

        $hook_names = array_column($actions, 0);

        $this->assertContains('admin_enqueue_scripts', $hook_names);
        $this->assertNotContains('admin_head', $hook_names);
    }

    /**
     * @test-id UT-329
     */
    public function test_UT_329_pro_upgrade_banner_includes_dismiss_link_when_not_dismissed(): void
    {
        ob_start();
        PPCart_Version_Notices::render_pro_upgrade_top_notice();
        $markup = (string) ob_get_clean();

        $this->assertStringContainsString('pp-version-notice-bold-purple-dismiss', $markup);
        $this->assertStringContainsString(PPCart_Version_Notices::DISMISS_PRO_UPGRADE_QUERY_ARG, $markup);
    }

    /**
     * @test-id UT-329
     */
    public function test_UT_329_pro_upgrade_banner_hidden_when_user_dismissed(): void
    {
        WordPressStubContext::setState(
            'user_meta',
            [
                1 => [
                    PPCart_Version_Notices::DISMISS_PRO_UPGRADE_META_KEY => '1',
                ],
            ]
        );

        $this->assertFalse(PPCart_Version_Notices::should_display_pro_upgrade_top_notice());
    }

    /**
     * @test-id UT-329
     */
    public function test_UT_329_pro_upgrade_banner_enqueues_styles_on_cart_screen(): void
    {
        $enqueued = [];
        $_GET['page'] = PPCart_Admin_Screens::PAGE_DASHBOARD;

        WordPressStubContext::set(
            'wp_enqueue_style',
            static function ($handle, $src, $dependencies, $version) use (&$enqueued) {
                $enqueued[] = [$handle, $src, $dependencies, $version];

                return true;
            }
        );

        PPCart_Version_Notices::maybe_print_pro_upgrade_notice_styles();

        $this->assertSame(
            [
                [
                    'ppcart-version-notices',
                    PPCART_BASE_URL . 'admin/css/ppcart-version-notices.css',
                    [],
                    PPCART_VERSION,
                ],
            ],
            $enqueued
        );
    }
}
