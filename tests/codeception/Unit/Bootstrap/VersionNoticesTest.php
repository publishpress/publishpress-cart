<?php

namespace unit\Bootstrap;

use Codeception\Test\Unit;
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
        WordPressStubContext::clear();
        WordPressStubContext::setState('filters', []);
        WordPressStubContext::setState('filter_overrides', []);
        WordPressStubContext::setState('caps', ['install_plugins']);

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

        if (! class_exists('PPCart_Version_Notices', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-version-notices.php';
        }
    }

    protected function _after(): void
    {
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
                'pp_version_notice_top_notice_settings',
                'pp_version_notice_menu_link_settings',
            ],
            WordPressStubContext::getState('filters', [])
        );
    }
}
